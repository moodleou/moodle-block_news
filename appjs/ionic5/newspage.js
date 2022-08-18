(function(t) {

    /**
     * @var {number} The number of the next page of messages to load.
     */
    var nextPage = 1;

    /**
     * @var {number} Message type for news
     */
    var TYPE_NEWS = 1;

    /**
     * Load the next page of news items for the infinite scroll.
     *
     * @param {Object} infiniteScroll
     */
    t.loadMoreMessages = function(infiniteScroll) {
        var args = {
            courseid: t.CONTENT_OTHERDATA.courseid,
            pagenum: nextPage,
            type: TYPE_NEWS,
            pastevents: 0
        }
        t.CoreSitesProvider.getCurrentSite().read('block_news_get_message_page', args).then(function(response) {
            t.CONTENT_OTHERDATA.moreMessages = response.moremessages;
            response.messages.forEach(function(message) {
                // Message and title can duplicate but id is not, so need to check message items are duplicated or not.
                var exist = t.CONTENT_OTHERDATA.messages.some(function(ms) {
                    if (ms.id === message.id) {
                        return true;
                    } else {
                        return false;
                    }
                });
                if(!exist) {
                    t.CONTENT_OTHERDATA.messages.push(message);
                }
            });
            nextPage++;
            if (t.CONTENT_OTHERDATA.moreMessages) {
                window.setTimeout(function() {
                    t.CoreUtilsProvider.blockNewsUtils.loadMessagesIfShortPage(infiniteScroll, t.loadMoreMessages);
                }, 0);
            } else {
                infiniteScroll.target.disabled = true;
            }
        }).finally(function() {
            infiniteScroll.target.complete();
        });
    };

    /**
     * Jump to a selected news.
     *
     * @param {number} messageId Message ID
     * @param {number} pageNum Page of news page
     */
    t.jumpToNews = function (messageId, pageNum) {
        var isMessageLoaded = t.CONTENT_OTHERDATA.messages.some(function(message) {
            return message.id === messageId;
        });
        if (pageNum === undefined) {
            pageNum = 0;
        }
        if (isMessageLoaded) {
            setTimeout(function() {
                var element = document.querySelector('#block-news-message-id-' + messageId);
                element.scrollIntoView({behavior: "smooth"});
            }, 100);
        } else {
            var args = {
                courseid: t.CONTENT_OTHERDATA.courseid,
                pagenum: pageNum,
                type: TYPE_NEWS,
                pastevents: 0
            }
            t.CoreSitesProvider.getCurrentSite().read('block_news_get_message_page', args).then(function(response) {
                t.CONTENT_OTHERDATA.moreMessages = response.moremessages;
                response.messages.forEach(function(message) {
                    t.CONTENT_OTHERDATA.messages.push(message);
                });
                pageNum++;
                if (t.CONTENT_OTHERDATA.moreMessages) {
                    window.setTimeout(function() {
                        t.jumpToNews(messageId, pageNum);
                    }, 0);
                } else {
                    // Scroll to news when all news are loaded.
                    setTimeout(function() {
                        var element = document.querySelector('#block-news-message-id-' + messageId);
                        if (element != null) {
                            element.scrollIntoView({behavior: "smooth"});
                        }
                    }, 100);
                }
            })
        }
    };

    window.initPageURL('news', t.CONTENT_OTHERDATA.pageurl);
    t.openInBrowser = window.openInBrowser;

    if (t.CONTENT_OTHERDATA.targetMessage) {
        t.jumpToNews(t.CONTENT_OTHERDATA.targetMessage, 1);
    } else {
        t.CoreUtilsProvider.blockNewsUtils.pageInit(t);
    }

})(this);
