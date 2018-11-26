(function(t) {
    t.CoreUtilsProvider.blockNewsUtils = {

        /**
         * @const {Integer} There are no more messages to load.
         */
        MOREMESSAGES_NO: 0,

        /**
         * @const {Integer} There are more messages to load.
         */
        MOREMESSAGES_YES: 1,

        /**
         * @const {Integer} There are no more upcoming events to load, switch to past events.
         */
        MOREMESSAGES_SWITCHMODE: 2,

        /**
         * Check whether the infitite scroll element is visible, and load more messages if so.
         *
         * @param {Object} infiniteScroll Object with enable and complete methods, can be real or faked.
         * @param {Function} loadMoreMessages Function to call to load and display more messages.
         */
        loadMessagesIfShortPage: function(infiniteScroll, loadMoreMessages) {
            if (!infiniteScroll.disabled) {
                var infiniteRect = document.getElementById('block_news_infinite_load_messages').getBoundingClientRect();
                if (infiniteRect.top >= 0 && infiniteRect.bottom <= window.innerHeight) {
                    if (infiniteScroll.hasOwnProperty('spinner')) {
                        window.clearTimeout(infiniteScroll.dismissTimeout);
                        infiniteScroll.spinner.style.display = 'block';
                    }
                    loadMoreMessages(infiniteScroll);
                }
            }
        },

        /**
         * Trigger loading or extra messages to fill the page if required, and set the content height.
         *
         * @param {CoreCompileFakeHTMLComponent} page Must define a loadMoreMessages function.
         */
        pageInit: function(page) {
            // Fix content size so that infinite scroll works.
            // This is ugly, hopefully there will be a proper way to do this once MOBILE-2770 is done.
            var views = page.NavController._views;
            views.forEach(function(view) {
                if (view.id === "CoreCourseSectionPage") {
                    view._cntDir.resize();
                }
            });

            window.setTimeout(function() {
                var fakeInfiniteScroll = {
                    disabled: false,
                    dismissTimeout: null,
                    spinner: document.getElementById('block_news_infinite_load_messages').querySelector('.infinite-loading'),
                    enable: function(state) {
                        this.disabled = !state;
                    },
                    complete: function() {
                        this.dismissTimeout = window.setTimeout(function() {
                            fakeInfiniteScroll.spinner.style.display = '';
                        }, 1000);
                    }
                };
                t.CoreUtilsProvider.blockNewsUtils.loadMessagesIfShortPage(fakeInfiniteScroll, page.loadMoreMessages);
            }, 0);
        }
    };
})(this);
