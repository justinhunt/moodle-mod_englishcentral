//EC iframe Resizer
(function () {
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
        document.querySelector('ion-content:not(.disable-scroll-y)').scrollTo({top: 80});
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