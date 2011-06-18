/**
 * Application: ApplicationFileManager
 *
 * @package ajwm.Applications
 * @author Anders Evenrud <andersevenrud@gmail.com>
 * @class
 */
var ApplicationFileManager = (function($, undefined) {
  return function(Window, Application, API, argv) {

    if ( argv.view_type == undefined ) {
      argv.view_type = 'icon';
    }
    if ( argv.path == undefined ) {
      argv.path = "/";
    }

    var _CurrentDir = "/";
    var _History = [];
    var lastItem;
    var _defaultStatusText = "";

    var Window_window1 = Window.extend({

      init : function(app) {
        this._super("ApplicationFileManager", false, {}, {});
        this.content = $("<div class=\"window1\"> <div class=\"GtkWindow ApplicationFileManager window1\"> <table class=\"GtkBox Vertical box1\"> <tr> <td class=\"Fill GtkBoxPosition Position_0\"> <ul class=\"GtkMenuBar menubar1\"> <li class=\"GtkMenuItem menuitem_new\"> <span><u>F</u>ile</span> <ul class=\"GtkMenu menu1\"> <li class=\"GtkImageMenuItem imagemenuitem_new\"> <img alt=\"gtk-new\" src=\"/img/icons/16x16/actions/gtk-new.png\"/> <span>New</span> </li> <li class=\"GtkImageMenuItem imagemenuitem_close\"> <img alt=\"gtk-close\" src=\"/img/icons/16x16/actions/gtk-close.png\"/> <span>Close</span> </li> </ul> </li> <li class=\"GtkMenuItem menuitem2\"> <span><u>G</u>o</span> <ul class=\"GtkMenu menu2\"> <li class=\"GtkImageMenuItem imagemenuitem_home\"> <img alt=\"gtk-home\" src=\"/img/icons/16x16/actions/gtk-home.png\"/> <span>Home</span> </li> </ul> </li> <li class=\"GtkMenuItem menuitem3\"> <span><u>V</u>iew</span> <ul class=\"GtkMenu menu3\"> <li class=\"GtkImageMenuItem menuitem_refresh\"> <img alt=\"gtk-refresh\" src=\"/img/icons/16x16/actions/gtk-refresh.png\"/> <span>Refresh</span> </li> <li class=\"GtkRadioMenuItem menuitem_list\"> <span>List view</span> </li> <li class=\"GtkRadioMenuItem menuitem_icon\"> <span>Icon View</span> </li> </ul> </li> </ul> </td> </tr> <tr> <td class=\"Expand Fill GtkBoxPosition Position_1\"> <div class=\"GtkIconView GtkObject iconview1\"></div> </td> </tr> <tr> <td class=\"Fill GtkBoxPosition Position_2\"> <div class=\"GtkStatusbar statusbar1\"></div> </td> </tr> </table> </div> </div> ").html();
        this.title = 'File Manager';
        this.icon = 'apps/file-manager.png';
        this.is_draggable = true;
        this.is_resizable = true;
        this.is_scrollable = false;
        this.is_sessionable = true;
        this.is_minimizable = true;
        this.is_maximizable = true;
        this.is_closable = true;
        this.is_orphan = false;
        this.width = 500;
        this.height = 500;
        this.gravity = null;


        this.app = app;
      },

      destroy : function() {
        this._super();
      },


      EventMenuNew : function(el, ev) {
        var self = this;

        API.system.dialog_upload(_CurrentDir, function() {
          self.chdir(_CurrentDir);
        });
      },


      EventMenuClose : function(el, ev) {
        this.$element.find(".ActionClose").click();
      },


      EventMenuHome : function(el, ev) {
        if ( _CurrentDir != "/" ) {
          this.chdir("/");
          _History = [];
          _CurrentDir = "/";
        }
      },


      EventRefresh : function(el, ev) {
        this.chdir(_CurrentDir);
      },


      EventMenuListToggle : function(el, ev) {
        this.app.argv.view_type = 'list';
        this.chdir(_CurrentDir);
        this._updateMenu();
      },


      EventMenuIconToggle : function(el, ev) {
        this.app.argv.view_type = 'icon';
        this.chdir(_CurrentDir);
        this._updateMenu();
      },


      EventIconviewSelect : function(el, ev) {
      },

      chdir : function(dir, hist) {
        var self = this;

        this.app.event(self, "browse", {"path" : dir, "view" : self.app.argv['view_type']}, function(result, error) {
          self._destroyView();

          if ( error ) {
            API.system.dialog("error", error);
            this.$element.find(".WindowTopInner span").html(self.title);

            _defaultStatusText = "";
          } else {
            self.$element.find(".ApplicationFileManager .iconview1").html(result.items);
            self.$element.find(".WindowTopInner span").html(self.title + ": " + result.path);

            _defaultStatusText = sprintf("%d items (%d bytes)", result.total, result.bytes);

            self._initClick();
          }
          self.$element.find(".statusbar1").html(_defaultStatusText);
        });

        _CurrentDir = dir;
        self.app.argv['path'] = _CurrentDir;

        /*if ( hist !== false ) {
          _History.push(_CurrentDir);
        }*/
      },

      _initClick : function () {
        var self = this;

        this.$element.find(".ApplicationFileManager .Inner").bind('click', function(ev) {
          $(document).click(); // Trigger this! (deselects context-menu)
          ev.stopPropagation();
        }).bind('dblclick', function(ev) {


          var fname = fpath =  ftype = fmime = null;

          if ( this.tagName == "td" ) {
            fname = $(this).parent().find("input[name=name]").val();
            fpath = $(this).parent().find("input[name=path]").val();
            ftype = $(this).parent().find("input[name=type]").val();
            fmime = $(this).parent().find("input[name=mime]").val();
          } else {
            fname = $(this).find("input[name=name]").val();
            fpath = $(this).find("input[name=path]").val();
            ftype = $(this).find("input[name=type]").val();
            fmime = $(this).find("input[name=mime]").val();
          }

          if ( ftype == "dir" ) {
            self.chdir(fpath);
          } else {
            API.system.run(fpath, fmime);
          }
          ev.stopPropagation();
          ev.preventDefault();
        }).bind('mousedown', function(ev) {
          self._selItem(this);

          ev.stopPropagation();
          ev.preventDefault();

          var pro = $(this).find("input[name=protected]").val() == "1";
          var path = $(this).find("input[name='path']").val();
          var fname = $(this).find("input[name='name']").val();

          if ( pro ) {
            return API.application.context_menu(ev, [
              {"title" : "Protected", "disabled" : true, "method" : function() {
                return false;
              }}
            ], $(this));
          } else {
            return API.application.context_menu(ev, [
              {"title" : "Delete", "method" : function() {
                API.system.dialog("confirm", "Are you sure you want to delete '" + fname + "'?", null, function() {
                  API.system.call("delete", path, function(result, error) {
                    if ( error === null ) {
                      self.chdir(_CurrentDir);
                    }
                  });
                });
              }},
              {"title" : "Rename", "method" : function() {
                API.system.dialog_rename(fname, function(nfname) {
                  API.system.call("rename", [path, fname, nfname], function(result, error) {
                    if ( error === null ) {
                      self.chdir(_CurrentDir);
                    }
                  });
                });
              }}/*,
              {"title" : "Download", "method" : function() {
                alert('Not implemented yet'); // TODO
              }}*/
            ], $(this));
          }
        });

        this.$element.find(".ApplicationFileManager li").addClass("ContextMenu");
        this.$element.find(".ApplicationFileManager td").addClass("ContextMenu");
      },


      _selItem : function(t) {
        var self = this;

        if ( lastItem ) {
          if ( t && t.tagName.toLowerCase() == "tr" ) {
            $(lastItem).removeClass("Current");
          } else {
            $(lastItem).parent().removeClass("Current");
          }
        }

        if ( t ) {
          if ( t.tagName.toLowerCase() == "tr" ) {
            $(t).addClass("Current");
          } else {
            $(t).parent().addClass("Current");
          }

          var name = $(t).find("input[name='name']").val();
          var size = $(t).find("input[name='size']").val();
          var mime = $(t).find("input[name='mime']").val();
          var type = $(t).find("input[name='type']").val();

          if ( type == "dir" ) {
            self.$element.find(".statusbar1").html(sprintf('"%s" %s', name, "folder"));
          } else {
            self.$element.find(".statusbar1").html(sprintf('"%s" (%s b) %s', name, size, mime));
          }
        } else {
          self.$element.find(".statusbar1").html(_defaultStatusText);
        }

        lastItem = t;
      },

      _destroyView : function() {
        this.$element.find(".ApplicationFileManager .iconview1 .Inner").unbind();
        this.$element.find(".ApplicationFileManager .iconview1 ul").die();
        this.$element.find(".ApplicationFileManager .iconview1 ul").unbind();
        this.$element.find(".ApplicationFileManager .iconview1 table").die();
        this.$element.find(".ApplicationFileManager .iconview1 table").unbind();
        this.$element.find(".ApplicationFileManager .iconview1 ul").remove();
        this.$element.find(".ApplicationFileManager .iconview1 table").remove();
      },

      _updateMenu : function() {
        if ( this.app.argv.view_type == 'icon' ) {
          this.$element.find(".menuitem_list").removeClass("Checked");
          this.$element.find(".menuitem_icon").addClass("Checked");
        } else {
          this.$element.find(".menuitem_list").addClass("Checked");
          this.$element.find(".menuitem_icon").removeClass("Checked");
        }
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



          el.find(".imagemenuitem_new").click(function(ev) {
            self.EventMenuNew(this, ev);
          });

          el.find(".imagemenuitem_close").click(function(ev) {
            self.EventMenuClose(this, ev);
          });

          el.find(".imagemenuitem_home").click(function(ev) {
            self.EventMenuHome(this, ev);
          });

          el.find(".menuitem_refresh").click(function(ev) {
            self.EventRefresh(this, ev);
          });

          el.find(".menuitem_list").click(function(ev) {
            self.EventMenuListToggle(this, ev);
          });

          el.find(".menuitem_icon").click(function(ev) {
            self.EventMenuIconToggle(this, ev);
          });

          el.find(".iconview1").click(function(ev) {
            self.EventIconviewSelect(this, ev);
          });

          // Do your stuff here

          el.addClass(this.name);



          $(el).click(function() {
            self._selItem();
          });


          this._updateMenu();
          this.chdir(argv.path);

        }

      }
    });


    ///////////////////////////////////////////////////////////////////////////
    // APPLICATION
    ///////////////////////////////////////////////////////////////////////////

    var __ApplicationFileManager = Application.extend({

      init : function() {
        this._super("ApplicationFileManager", argv);
      },

      destroy : function() {
        this._super();
      },

      run : function() {
        var self = this;

        this._super();

        var root_window = new Window_window1(self);
        root_window.show(self);

        // Do your stuff here
      }
    });

    return new __ApplicationFileManager();
  };
})($);
