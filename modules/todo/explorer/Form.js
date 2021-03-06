
sylma.modules.todo.Form = new Class({

  Extends : sylma.crud.FormAjax,

  submit : function(e, args, callback) {

    this.parent(e, args, callback);
  },

  showMask : function() {

    this.getParent('side').startLoading();
  },

  hideMask : function() {

    this.getParent('side').stopLoading();
  },

  submitSuccess : function() {

    this.hideMask();
    this.getParent('task').disabled = true;
    //this.getParent('task').toggleSide(true, true, false);

    if (this.getMode() === 'insert') {

      this.removeNew();
    }
    
    this.getParent('explorer').updateCollection();
  },

  deleteSuccess : function() {

    this.getParent('task').remove();
  },

  getMode : function() {

    return this.options.mode;
  },

  removeNew : function() {

    this.getParent('task').remove();
  },

  cancel : function () {

    this.getParent('task').toggleSide(false, true);

    if (this.getMode() === 'insert') {

      this.removeNew();
    }
  }
});