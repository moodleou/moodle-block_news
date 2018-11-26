(function(t) {

    /**
     * @var {number} The number of the next page of messages to load.
     */
    var nextPage = 1;
    /**
     * @var {number} Message type for events
     */
    var TYPE_EVENT = 2;
    /**
     * @var {Array} The list of messages to append results to.
     */
    var eventsList = t.CONTENT_OTHERDATA.messages;
    /**
     * @var {Integer} Are we fetching past events? Starts 0, switches to 1 when we run out of upcoming events.
     */
    t.pastEvents = 0;

    /**
     * Load more events messages.
     *
     * To start with, load the next page of upcoming events.  If this page is empty, set the pastEvents flag and
     * re-run the function to load the first page of past events.  Subsequent calls will load past events until
     * we run out.
     *
     * @param {Object} infiniteScroll
     */
    t.loadMoreMessages = function(infiniteScroll) {
        var util = t.CoreUtilsProvider.blockNewsUtils;
        var args = {
            courseid: t.NavController.getActive().data.course.id,
            pagenum: nextPage,
            type: TYPE_EVENT,
            pastevents: t.pastEvents
        };
        t.CoreSitesProvider.getCurrentSite().read('block_news_get_message_page', args).then(function(response) {
            t.CONTENT_OTHERDATA.moreMessages = response.moremessages;
            response.messages.forEach(function(message) {
                eventsList.push(message);
            });
            nextPage++;
            if (t.CONTENT_OTHERDATA.moreMessages > util.MOREMESSAGES_NO) {
                if (t.CONTENT_OTHERDATA.moreMessages === util.MOREMESSAGES_SWITCHMODE) {
                    t.pastEvents = 1;
                    nextPage = 0;
                    eventsList = t.CONTENT_OTHERDATA.pastEvents;
                }
                window.setTimeout(function() {
                    util.loadMessagesIfShortPage(infiniteScroll, t.loadMoreMessages);
                }, 0);
            } else {
                t.pastEvents = 1;
                infiniteScroll.enable(false);
            }
        }).finally(function() {
            infiniteScroll.complete();
        });
    };

    t.CoreUtilsProvider.blockNewsUtils.pageInit(t);

})(this);
