/*!
 * PanelItem: Clock
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
 * @package OSjs.Panel
 * @author Anders Evenrud <andersevenrud@gmail.com>
 * @licence Simplified BSD License
 * @class
 */
OSjs.PanelItems.PanelItemClock = (function($, undefined) {
  "$:nomunge";

  return function(_PanelItem, panel, API, argv) {
    "_PanelItem:nomunge, panel:nomunge, API:nomunge, argv:nomunge";

    var LABELS = OSjs.Labels.PanelItemClock;

    var _PanelItemClock = _PanelItem.extend({
      init : function() {
        this._super("PanelItemClock", "right");
        this._named = LABELS.title;
      },

      create : function(pos) {
        var ret = this._super(pos);
        $(ret).append("<span></span>");

        //var d = new Date();
        //$(ret).find("span").html(sprintf("%02d/%02d/%02d %02d:%02s", d.getDate(), d.getMonth(), d.getFullYear(), d.getHours(), d.getMinutes()));
        $(ret).find("span").html(format_date(API.user.settings.get("system.locale.timestamp-format")));

        // Start clock
        this.clock_interval = setInterval(function() {
          //var d = new Date();
          //$(ret).find("span").html(sprintf("%02d/%02d/%02d %02d:%02s", d.getDate(), d.getMonth(), d.getFullYear(), d.getHours(), d.getMinutes()));
          $(ret).find("span").html(format_date(API.user.settings.get("system.locale.timestamp-format")));
        }, 500);

        return ret;
      },


      destroy : function() {
        if ( this.clock_interval ) {
          clearInterval(this.clock_interval);
        }

        this._super();
      }
    });

    return construct(_PanelItemClock, argv);
  };
})($);
