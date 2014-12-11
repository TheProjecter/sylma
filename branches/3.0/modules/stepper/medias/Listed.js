sylma.stepper = sylma.stepper || {};

sylma.stepper.Listed = new Class({

  currentKey : -1,

  setCurrent : function(key) {

    this.currentKey = key || 0;
  },

  getCurrent : function() {

    return this.currentKey;
  },

  testLast : function(items, key, callback) {

    this.testItem(items, key, callback);
  },

  testItems : function(items, key, callback, record) {

    key = key || 0;

    items[key].test(function() {

      this.testNextItem(items, key + 1, callback, record);

    }.bind(this));
  },

  testNextItem : function(items, key, callback, record) {

    var length = items.length;

    if (key === length - 1) {

      this.testLast(items, key, callback, record);
    }
    else if (key < length) {

      this.testItem(items, key, callback, record);
    }
    else if (callback)  {

      callback();
    }
  },

  /**
   * Available for override
   */
  testItem : function(items, key, callback, record) {

    this.testItems(items, key, callback, record);
  }
});