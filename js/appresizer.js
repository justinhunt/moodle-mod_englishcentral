//EC iframe Resizer
(function () {

    console.log("appresizer.js initializing");

    // Scroll the page content higher
    var availableRetries=5;
    function disableCollapsibleHeader() {
        console.log("collapsing header .. retries left:"+availableRetries);
        console.log("doing disable collapsible header");// First select the current page.
        var page = document.querySelector('page-core-site-plugins-module-index.ion-page.collapsible-header-page.collapsible-header-page-is-active');
        if (!page) {
            if(availableRetries>0) {
                availableRetries--;
                setTimeout(disableCollapsibleHeader, 1000);
                console.log("no page so unable to disable collapsible header ..  try again:" + availableRetries);
            }else{
                console.log("no page so unable to disable collapsible header ..  giving up:");
            }
            return;
        }

        // Disable the collapsible-header
        page.style.setProperty('--collapsible-header-progress', 1);
        page.classList.add('collapsible-header-page-is-collapsed');
        // Hide the floating title
        page.querySelector('.collapsible-header-floating-title-wrapper').style.display = 'none';
        // Optionally, hide all the ion-item.
        page.querySelector('.collapsible-header-expanded').style.display = 'none';
        document.querySelector('ion-content:not(.disable-scroll-y)').scrollTo({top: 80});
    }

    //try it once
    disableCollapsibleHeader();

})();