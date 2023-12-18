//EC iframe Resizer
(function () {
    if ( !window.addEventListener || window.ecappResizerInitialized) {
        console.log("appresizer.js cant run without a window");
        return; // Not supported
    }
    console.log("appresizer.js initializing");
    window.ecappResizerInitialized = true;
    // Scroll the page content 120 pixels higher
    // Function to scroll the specified element by its own height
    function scrollElementUpByHeight() {
        var theclassname = 'collapsible-header-expanded';
        var element = document.querySelector('.' + theclassname);

        if (element) {
            // Scroll the element up by its own height
            var offset= element.offsetHeight;
            window.scrollBy(0, offset);
            console.log("appresizer.js scrolling up by " + offset + " pixels");

        }else{
            console.log("appresizer.js no element with class " + theclassname + " found");
        }
    }

    // Attach the event listener to the 'load' event of the iframe
    window.addEventListener('load', function(e){
        document.getElementById('englishcentral-mobileapp-iframe').addEventListener('load', scrollElementUpByHeight);
        //just in case
        scrollElementUpByHeight();
    });
    //


})();