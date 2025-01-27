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

    /* Register a link handler to open blocks/news/message links anywhere in the app. */
    class AddonBlockNewsLinkToPageHandler extends t.CoreContentLinksHandlerBase {
        constructor() {
            super();
            this.pattern = new RegExp("\/blocks\/news\/message\\.php\\?m=(\\d+)");
            this.name = "AddonBlockNewsLinkToPageHandler";
            this.priority = 0;
        }
        getActions(siteIds, url, params) {
            var action = {
                action: function(siteId) {
                    t.CoreSitesProvider.getSite(siteId).then(function(site) {
                        site.read('block_news_get_courseid_from_messageid', {messageid: parseInt(params.m, 10)}).then(function(result) {
                            if (result) {
                                var args = {
                                    contextlevel: result.contextLevel,
                                    instanceid: result.instanceid,
                                    courseid: result.courseid,
                                    messageid: result.messageid
                                };
                                var hash = t.Md5.hashAsciiStr(JSON.stringify(args));
                                var pageParams = {
                                    title: result.title,
                                    args,
                                    component: 'block_news',
                                    method: 'news_page'
                                };
                                t.CoreNavigatorService.navigateToSitePath('siteplugins/content/block_news/news_page/' + hash, { params: pageParams });
                            }
                        });
                    });
                }
            };
            return [action];
        }
    };
    t.CoreContentLinksDelegate.registerHandler(new AddonBlockNewsLinkToPageHandler());

})(this);
