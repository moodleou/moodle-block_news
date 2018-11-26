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
            courseid: t.NavController.getActive().data.course.id,
            pagenum: nextPage,
            type: TYPE_NEWS,
            pastevents: 0
        }
        t.CoreSitesProvider.getCurrentSite().read('block_news_get_message_page', args).then(function(response) {
            t.CONTENT_OTHERDATA.moreMessages = response.moremessages;
            response.messages.forEach(function(message) {
               t.CONTENT_OTHERDATA.messages.push(message);
            });
            nextPage++;
            if (t.CONTENT_OTHERDATA.moreMessages) {
                window.setTimeout(function() {
                    t.CoreUtilsProvider.blockNewsUtils.loadMessagesIfShortPage(infiniteScroll, t.loadMoreMessages);
                }, 0);
            } else {
                infiniteScroll.enable(false);
            }
        }).finally(function() {
            infiniteScroll.complete();
        });
    };

    t.CoreUtilsProvider.blockNewsUtils.pageInit(t);

})(this);
