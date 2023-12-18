//EC iframe Resizer
(function () {
    //if ( !window.addEventListener || window.ecappResizerInitialized) {
     if ( !window.addEventListener ) {
        console.log("appresizer.js cant run without a window");
        return; // Not supported
    }
    console.log("appresizer.js initializing");
    window.ecappResizerInitialized = true;
    // Scroll the page content 120 pixels higher
    // Function to scroll the specified element by its own height
    function scrollElementUpByHeight() {
        console.log("appresizer.js scrolling");
        var collapsed = true;
        var thepage = document.querySelector('page-core-site-plugins-module-index');
        if(thepage) {
            console.log("appresizer.js collapsing");
            thepage.classList.toggle('collapsible-header-page-is-collapsed', collapsed);
            CoreEvents.trigger(COLLAPSIBLE_HEADER_UPDATED, {collapsed});
            console.log("appresizer.js collapsed");
        }else{
            console.log("appresizer.js no page found");
        }
    }

    // Attach the event listener to the 'load' event of the iframe
    window.addEventListener('load', function(e){
        console.log("appresizer.js windowload");
        document.getElementById('englishcentral-mobileapp-iframe').addEventListener('load', scrollElementUpByHeight);
        //just in case
        scrollElementUpByHeight();
    });
    scrollElementUpByHeight();


})();