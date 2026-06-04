// Text-to-Speech player — Feature 7.0 (DSA accessibility support)
// Used standalone in simulator; in study/play the methods are inlined into studyPlay().
window.ttsPlayer = function ttsPlayer(text, { autoplay = false } = {}) {
    return {
        text,
        speaking: false,
        supported: 'speechSynthesis' in window,

        speak() {
            if (!this.supported) return;
            window.speechSynthesis.cancel();
            const utt = new SpeechSynthesisUtterance(this.text);
            utt.lang = document.documentElement.lang || 'it-IT';
            utt.onend  = () => { this.speaking = false; };
            utt.onerror = () => { this.speaking = false; };
            this.speaking = true;
            window.speechSynthesis.speak(utt);
        },

        stop() {
            if (!this.supported) return;
            window.speechSynthesis.cancel();
            this.speaking = false;
        },

        toggle() {
            this.speaking ? this.stop() : this.speak();
        },

        init() {
            if (autoplay && this.supported) this.speak();
        },

        destroy() {
            this.stop();
        },
    };
};
