'use strict';

// Exposes window.offlineStore — loaded as a Vite entry on pages that need it.

const DB_NAME    = 'scuolaguida_offline';
const DB_VERSION = 1;

let _db = null;

function openDB() {
    if (_db) return Promise.resolve(_db);

    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);

        req.onupgradeneeded = (event) => {
            const db = event.target.result;

            if (!db.objectStoreNames.contains('questions')) {
                const qs = db.createObjectStore('questions', { keyPath: 'id' });
                qs.createIndex('category_id', 'category_id', { unique: false });
                qs.createIndex('last_fetched_at', 'last_fetched_at', { unique: false });
            }

            if (!db.objectStoreNames.contains('categories')) {
                db.createObjectStore('categories', { keyPath: 'id' });
            }

            if (!db.objectStoreNames.contains('pending_answers')) {
                const pa = db.createObjectStore('pending_answers', { keyPath: 'id', autoIncrement: true });
                pa.createIndex('synced', 'synced', { unique: false });
            }
        };

        req.onsuccess = (event) => {
            _db = event.target.result;
            resolve(_db);
        };

        req.onerror = () => reject(req.error);
    });
}

// ──────────────────────────────────────────────────────────────────────────────
// Questions
// ──────────────────────────────────────────────────────────────────────────────

function saveQuestions(questions) {
    return openDB().then(db => new Promise((resolve, reject) => {
        const now    = new Date().toISOString();
        const tx     = db.transaction(['questions', 'categories'], 'readwrite');
        const qStore = tx.objectStore('questions');
        const cStore = tx.objectStore('categories');

        questions.forEach(q => {
            qStore.put({ ...q, last_fetched_at: now });
            if (q.category) cStore.put(q.category);
        });

        tx.oncomplete = () => resolve(questions.length);
        tx.onerror    = () => reject(tx.error);
    }));
}

function getAllQuestions(limit) {
    limit = limit || 100;
    return openDB().then(db => new Promise((resolve, reject) => {
        const tx      = db.transaction('questions', 'readonly');
        const store   = tx.objectStore('questions');
        const results = [];

        const req = store.openCursor();
        req.onsuccess = (event) => {
            const c = event.target.result;
            if (c && results.length < limit) {
                results.push(c.value);
                c.continue();
            } else {
                resolve(results);
            }
        };
        req.onerror = () => reject(req.error);
    }));
}

function getQuestionsByCategory(categoryId, limit) {
    limit = limit || 50;
    return openDB().then(db => new Promise((resolve, reject) => {
        const tx    = db.transaction('questions', 'readonly');
        const index = tx.objectStore('questions').index('category_id');
        const req   = index.getAll(IDBKeyRange.only(categoryId), limit);

        req.onsuccess = () => resolve(req.result || []);
        req.onerror   = () => reject(req.error);
    }));
}

function getQuestionsCount() {
    return openDB().then(db => new Promise((resolve, reject) => {
        const req = db.transaction('questions', 'readonly').objectStore('questions').count();
        req.onsuccess = () => resolve(req.result);
        req.onerror   = () => reject(req.error);
    }));
}

// ──────────────────────────────────────────────────────────────────────────────
// Pending answers
// ──────────────────────────────────────────────────────────────────────────────

function enqueuePendingAnswer(payload) {
    return openDB().then(db => new Promise((resolve, reject) => {
        const tx  = db.transaction('pending_answers', 'readwrite');
        const req = tx.objectStore('pending_answers').add({
            question_id:  payload.question_id,
            user_answer:  payload.user_answer,
            is_correct:   payload.is_correct,
            answered_at:  payload.answered_at || new Date().toISOString(),
            synced:       0,
        });

        req.onsuccess = () => resolve(req.result);
        req.onerror   = () => reject(req.error);
    }));
}

function getPendingAnswers() {
    return openDB().then(db => new Promise((resolve, reject) => {
        const tx    = db.transaction('pending_answers', 'readonly');
        const index = tx.objectStore('pending_answers').index('synced');
        const req   = index.getAll(IDBKeyRange.only(0));

        req.onsuccess = () => resolve(req.result || []);
        req.onerror   = () => reject(req.error);
    }));
}

function markAnswersSynced(ids) {
    if (!ids || !ids.length) return Promise.resolve();

    return openDB().then(db => new Promise((resolve, reject) => {
        const tx    = db.transaction('pending_answers', 'readwrite');
        const store = tx.objectStore('pending_answers');

        ids.forEach(id => {
            const getReq = store.get(id);
            getReq.onsuccess = () => {
                if (getReq.result) store.put({ ...getReq.result, synced: 1 });
            };
        });

        tx.oncomplete = () => resolve();
        tx.onerror    = () => reject(tx.error);
    }));
}

// ──────────────────────────────────────────────────────────────────────────────
// Global export
// ──────────────────────────────────────────────────────────────────────────────

window.offlineStore = {
    saveQuestions,
    getAllQuestions,
    getQuestionsByCategory,
    getQuestionsCount,
    enqueuePendingAnswer,
    getPendingAnswers,
    markAnswersSynced,
};
