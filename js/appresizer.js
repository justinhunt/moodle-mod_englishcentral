//EC iframe Resizer
(function () {
     if ( !window.addEventListener ) {
        console.log("appresizer.js cant run yet");
        return; // Not supported
    }
    console.log("appresizer.js initializing");

    // Scroll the page content higher
    function scrollElementUpByHeight() {
        console.log("appresizer.js scrolling");
        document.querySelector('ion-content:not(.disable-scroll-y)').scrollTo({top: 80});
    }

    // Attach the event listener to the 'load' event of the iframe
    window.addEventListener('load', function(e){
        console.log("appresizer.js windowload");
        //try it again
        document.getElementById('englishcentral-mobileapp-iframe').addEventListener('load', scrollElementUpByHeight);
        //try it again
        scrollElementUpByHeight();
    });
    //try it once
    scrollElementUpByHeight();


})();