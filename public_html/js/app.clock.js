/**
 * Application: ApplicationClock
 *
 * @package ajwm.Applications
 * @author Anders Evenrud <andersevenrud@gmail.com>
 * @class
 */
var ApplicationClock = (function($, undefined) {
  return function(Window, Application, API, argv) {



    var Window_window1 = Window.extend({

      init : function(app) {
        this._super("ApplicationClock", false, {}, {});
        this.content = $("<div class=\"window1\"> <div class=\"GtkWindow ApplicationClock window1\"> <div class=\"Clock\"><div class=\"HourShadow\"></div><div class=\"Hour\"></div><div class=\"MinuteShadow\"></div><div class=\"Minute\"></div><div class=\"SecondShadow\"></div><div class=\"Second\"></div></div> </div> </div> ").html();
        this.title = 'Clock';
        this.icon = 'status/appointment-soon.png';
        this.is_draggable = true;
        this.is_resizable = false;
        this.is_scrollable = false;
        this.is_sessionable = true;
        this.is_minimizable = false;
        this.is_maximizable = false;
        this.is_closable = true;
        this.is_orphan = false;
        this.width = 200;
        this.height = 200;
        this.gravity = 'center';

        this.app = app;

        this.int_sec = null;
        this.int_min = null;
        this.int_hour = null;
      },

      destroy : function() {
        clearTimeout(this.int_sec);
        clearTimeout(this.int_min);
        clearTimeout(this.int_hour);

        this._super();
      },



      create : function(id, zi, mcallback) {
        var el = this._super(id, zi, mcallback);
        var self = this;

        if ( el ) {
          el.find(".GtkScale").slider();

          el.find(".GtkToolItemGroup").click(function() {
            $(this).parents(".GtkToolPalette").first().find(".GtkToolItemGroup").removeClass("Checked");

            if ( $(this).hasClass("Checked") ) {
              $(this).removeClass("Checked");
            } else {
              $(this).addClass("Checked");
            }
          });

          el.find(".GtkToggleToolButton button").click(function() {
            if ( $(this).parent().hasClass("Checked") ) {
              $(this).parent().removeClass("Checked");
            } else {
              $(this).parent().addClass("Checked");
            }
          });



          // Do your stuff here

          var hour = $(el).find(".Hour, .HourShadow");
          var min  = $(el).find(".Minute, .MinuteShadow");
          var sec  = $(el).find(".Second, .SecondShadow");

          this.int_sec = setInterval( function() {
            var d = new Date();
            var seconds = d.getSeconds();
            var sdegree = seconds * 6;
            var srotate = "rotate(" + sdegree + "deg)";

            sec.css("-webkit-transform", srotate );

          }, 1000 );

          this.int_hour = setInterval( function() {
            var d = new Date();
            var hours = d.getHours();
            var mins = d.getMinutes();
            var hdegree = hours * 30 + Math.round(mins / 2);
            var hrotate = "rotate(" + hdegree + "deg)";

            hour.css("-webkit-transform", hrotate );

          }, 1000 );

          this.int_min = setInterval( function() {
            var d = new Date();
            var mins = d.getMinutes();
            var mdegree = mins * 6;
            var mrotate = "rotate(" + mdegree + "deg)";

            min.css("-webkit-transform", mrotate );
          }, 1000 );
        }

      }
    });


    ///////////////////////////////////////////////////////////////////////////
    // APPLICATION
    ///////////////////////////////////////////////////////////////////////////

    var __ApplicationClock = Application.extend({

      init : function() {
        this._super("ApplicationClock", argv);
      },

      destroy : function() {
        this._super();
      },

      run : function() {
        var self = this;

        this._super(self);

        var root_window = new Window_window1();
        root_window.show(self);

        // Do your stuff here
      }
    });

    return new __ApplicationClock();
  };
})($);

