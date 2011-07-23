$.widget('blueimpDropZoneEffects.fileupload', $.blueimpUI.fileupload, {

    options: {
        dropZone: $()
    },

    _dropZoneActivate: function () {
        this.options.dropZone.addClass(
            'ui-state-active',
            'normal'
        );
        this._dropZoneActive = true;
    },

    _dropZoneDeactivate: function () {
        this.options.dropZone.removeClass(
            'ui-state-active',
            'normal'
        );
        this._dropZoneActive = false;
    },

    _dropZoneHighLight: function (dropZone) {
        dropZone.toggleClass(
            'ui-state-highlight',
            'normal'
        );
    },

    _dropZoneDragEnter: function (e) {
        var fu = e.data.fileupload;
        fu._dropZoneHighLight($(e.target));
    },
    
    _dropZoneDragLeave: function (e) {
        var fu = e.data.fileupload;
        fu._dropZoneHighLight($(e.target));
    },

    _documentDragEnter: function (e) {
        var fu = e.data.fileupload;
        if (!fu._dropZoneActive) {
            fu._dropZoneActivate();
        }
    },

    _documentDragOver: function (e) {
        var fu = e.data.fileupload;
        clearTimeout(fu._dragoverTimeout);
        fu._dragoverTimeout = setTimeout(function () {
            fu._dropZoneDeactivate();
        }, 200);
    },

    _create: function () {
        if (this.options.dropZone && !this.options.dropZone.length) {
            this.options.dropZone = this.element.find('.dropzone-container div');
        }
        $.blueimpUI.fileupload.prototype._create.call(this);
        var ns = this.options.namespace || this.name;
        this.options.dropZone
            .bind('dragenter.' + ns, {fileupload: this}, this._dropZoneDragEnter)
            .bind('dragleave.' + ns, {fileupload: this}, this._dropZoneDragLeave);
        $(document)
            .bind('dragenter.' + ns, {fileupload: this}, this._documentDragEnter)
            .bind('dragover.' + ns, {fileupload: this}, this._documentDragOver);
    },
    
    destroy: function () {
        var ns = this.options.namespace || this.name;
        this.options.dropZone
            .unbind('dragenter.' + ns, this._dropZoneDragEnter)
            .unbind('dragleave.' + ns, this._dropZoneDragLeave);
        $(document)
            .unbind('dragenter.' + ns, this._documentDragEnter)
            .unbind('dragover.' + ns, this._documentDragOver);
        $.blueimpUI.fileupload.prototype.destroy.call(this);
    }

});