/*!
 * OperationDialog: LaunchOperationDialog
 * Used for selecting an application to open from a specific file (MIME)
 *
 * Copyright (c) 2011, Anders Evenrud
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 * 
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer. 
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution. 
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package OSjs.Dialogs
 * @author Anders Evenrud <andersevenrud@gmail.com>
 * @licence Simplified BSD License
 * @class
 */
OSjs.Dialogs.LaunchOperationDialog = (function($, undefined) {
  "$:nomunge";

  return function(OperationDialog, API, argv) {
    "OperationDialog:nomunge, API:nomunge, argv:nomunge";

    var LABELS = OSjs.Labels.LaunchOperationDialog;

    var _LaunchOperationDialog = OperationDialog.extend({
      init : function(items, clb_finish, not_found) {
        this.list         = items        || [];
        this.clb_finish   = clb_finish   || function() {};
        this.not_found    = not_found === undefined ? false : not_found;

        this._super("Launch");
        this._title    = LABELS.title;
        this._content  = $("#OperationDialogLaunch").html();
        this._width    = 400;
        this._height   = 300;
      },


      create : function(id, mcallback) {
        var self = this;
        this._super(id, mcallback);

        var app, current;
        var selected;
        var set_default = false;

        if ( this.not_found ) {
          this.$element.find(".OperationDialogInner").prepend("<p>" + LABELS.not_found + "</p>");
        } else {
          this.$element.find(".OperationDialogInner").prepend("<p>" + LABELS.found + "</p>");
        }

        for ( var x = 0; x < this.list.length; x++ ) {
          app = this.list[x];
          var li = $("<li><img alt=\"\" src=\"/img/icons/16x16/" + app.icon + "\" /><span>" + app.title + "</span></li>");
          li.addClass(x % 2 ? "odd" : "even");
          (function(litem, mapp) {
            li.click(function() {
              if ( current && current !== this ) {
                $(current).removeClass("current");
              }
              current = this;
              selected = mapp.name; // must be appended

              $(current).addClass("current");
              self.$element.find(".Ok").removeAttr("disabled");
            }).dblclick(function() {
              if ( !self.$element.find(".Ok").attr("disabled") ) {
                self.$element.find(".Ok").click();
              }
            });
          })(li, app);
          this.$element.find("ul").append(li);
        }

        this.$element.find(".DialogButtons .Close").hide();
        this.$element.find(".DialogButtons .Ok").show().click(function() {
          var chk = self.$element.find("input[type=checkbox]");
          if ( selected ) {
            set_default = (chk.is(':checked')) ? true : false;
            self.clb_finish(selected, set_default);
          }
        }).attr("disabled", "disabled");
        this.$element.find(".DialogButtons .Cancel").show();
      }
    });

    return construct(_LaunchOperationDialog, argv);
  };
})($);
