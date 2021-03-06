sylma.stepper.PropertyClass = {

  Extends : sylma.stepper.Framed,

  onLoad: function() {

    if (this.options.name) {

      this.getSelect().set('value', this.options.name);
    }
  },

  getSelect : function() {

    return this.getNode('name');
  },

  getName: function() {

    return this.options.name;
  },

  onChange : function() {

    var el = this.getParent().getSelector().getElement();
    var key = this.getSelect().getSelected().get('value')[0];
    var result;

    if (el) {

      result = this.getValue(key, el);

      this.options.name = key;
      this.options.value = result === undefined ? '' : result;

      this.updateValue();
    }

    return el;
  },

  getValue : function(key, el) {

    var result;

    switch (key) {

      case 'content' :

        result = el.get('text');
        break;

      case 'children' :

        result = el.getChildren().length;
        break;

      case 'class' :

        result = el.get('class');
        break;

      case 'display' :

        var position = el.getPosition();
        var size = el.getSize();

        result = [position.x, position.y, size.x, size.y].join(';');
        break;

      case 'iframe' :

        result = 1;
        break;

      default :

        result = el.getStyle(key).toInt();
    }

    return result;
  },

  refresh : function() {

    if (this.onChange()) {

      this.getParent().hasError(false);
    }
  },

  reset : function() {

    this.ready = false;
    this.binded = false;
  },

  onFrameLoad : function(el) {

    el.removeEvent('load', this.onFrameLoad);
    this.ready = true;
  },

  test : function(el) {

    var value = this.parseVariables(this.options.value).content;
    var result;

    switch (this.options.name) {

      case 'content' :

        result = el.get('text') === value;
        break;

      case 'children' :

        result = el.getChildren().length === value.toInt();
        break;

      case 'class' :

        result = el.hasClass(value);
        break;

      case 'display' :

        var vals = value.split(';');
        var position = el.getPosition();
        var size = el.getSize();
//console.log(position.x,vals[0], '|', position.y,vals[1], '|', vals[2] && size.y,vals[3]);
        result = position.x == vals[0] && position.y == vals[1] && size.x == vals[2] && size.y == vals[3];
        break;

      case 'iframe' :

        if (!this.binded) {

          this.binded = true;
          el.addEvent('load', this.onFrameLoad.bind(this, el));
        }

        result = this.ready;
        break;

      default :

        result = el.getComputedStyle(this.options.name).toInt() === value.toInt();
    }

    return result;
  },

  updateValue : function(val) {

    if (val !== undefined) this.options.value = val;
    this.getNode('value').set('value', this.options.value);
  },

  toJSON : function() {

    return {
      '@name' : this.options.name || 'default',
      0 : this.options.value
    };
  }
};

sylma.stepper.Property = new Class(sylma.stepper.PropertyClass);
