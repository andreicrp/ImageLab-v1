/**
 * ImageLab Editor History Manager (Phase 3)
 * Manages the Undo/Redo stack for Fabric.js states (Max Depth: 50)
 */
const EditorHistory = {
    states: [],
    index: -1,
    maxStates: 50,
    isOperating: false, // Prevents pushing states during undo/redo operations

    /**
     * Push a new state snapshot to the history stack
     * @param {string} serializedCanvas JSON serialization of the Fabric.js canvas
     */
    push(serializedCanvas) {
        if (this.isOperating) return;

        // If we are in the middle of undo stack, truncate anything forward
        if (this.index < this.states.length - 1) {
            this.states = this.states.slice(0, this.index + 1);
        }

        // Push new state
        this.states.push(serializedCanvas);

        // Cap memory leak depth at maxStates
        if (this.states.length > this.maxStates) {
            this.states.shift();
        } else {
            this.index++;
        }

        this.updateButtonsUI();
    },

    /**
     * Revert to previous state
     * @returns {string|null} Serialized canvas state or null
     */
    undo() {
        if (!this.canUndo()) return null;
        
        this.isOperating = true;
        this.index--;
        const state = this.states[this.index];
        this.updateButtonsUI();
        return state;
    },

    /**
     * Restore next state
     * @returns {string|null} Serialized canvas state or null
     */
    redo() {
        if (!this.canRedo()) return null;

        this.isOperating = true;
        this.index++;
        const state = this.states[this.index];
        this.updateButtonsUI();
        return state;
    },

    canUndo() {
        return this.index > 0;
    },

    canRedo() {
        return this.index < this.states.length - 1;
    },

    clear() {
        this.states = [];
        this.index = -1;
        this.isOperating = false;
        this.updateButtonsUI();
    },

    /**
     * Synchronize disabled state of Undo/Redo UI buttons
     */
    updateButtonsUI() {
        const btnUndo = document.getElementById('btn-editor-undo');
        const btnRedo = document.getElementById('btn-editor-redo');

        if (btnUndo) btnUndo.disabled = !this.canUndo();
        if (btnRedo) btnRedo.disabled = !this.canRedo();

        // Render visual history panel log
        const historyList = document.getElementById('editor-history-list');
        if (historyList) {
            historyList.innerHTML = this.states.map((state, idx) => {
                const isActive = idx === this.index;
                return `
                    <div class="d-flex align-items-center justify-content-between p-2 rounded mb-1 ${isActive ? 'bg-primary-subtle text-primary fw-semibold' : 'bg-light small'}">
                        <span><i class="fa-solid fa-clock-history me-2"></i>State #${idx + 1}</span>
                        ${isActive ? '<span class="badge bg-primary">Active</span>' : ''}
                    </div>
                `;
            }).join('');
        }
    }
};
