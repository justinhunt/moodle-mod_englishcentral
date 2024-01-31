//EC iframe Resizer
(function () {

    console.log("appresizer.js initializing");

    // Scroll the page content higher
    function disableCollapsibleHeader() {
        console.log("doing disable collapsible header");// First select the current page.
        var page = document.querySelector('page-core-site-plugins-module-index.ion-page.collapsible-header-page.collapsible-header-page-is-active');
        if (!page) {console.log("no page so unable to disable collapsible header");return;}

        // Disable the collapsible-header
        page.style.setProperty('--collapsible-header-progress', 1);
        page.classList.add('collapsible-header-page-is-collapsed');
        // Hide the floating title
        page.querySelector('.collapsible-header-floating-title-wrapper').style.display = 'none';
        // Optionally, hide all the ion-item.
        page.querySelector('.collapsible-header-expanded').style.display = 'none';
        document.querySelector('ion-content:not(.disable-scroll-y)').scrollTo({top: 80});
    }

    // Attach the event listener to the 'load' event of the iframe
    window.addEventListener('load', function(e){
        console.log("appresizer.js windowload event");
        console.log("register event to collapse header #2");
        document.getElementById('englishcentral-mobileapp-iframe').addEventListener('load', disableCollapsibleHeader);

    });
    //try it once
    console.log("collapsing header #1");
    disableCollapsibleHeader();
    
})();