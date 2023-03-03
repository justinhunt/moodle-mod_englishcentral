/* eslint-disable max-len */
/* eslint-disable no-console */
define(function () {

  return {
    init: function () {
      let idSearchContainer = document.getElementById('id_searchcontainer');
      let fauxsearchButton = document.getElementById('faux-search-button');
      let closeSearchButton = document.getElementById('close-search-button');
      let searchBox = document.querySelector('.search-box');
      let searchResults = document.querySelector('.search-results');
      // When clicking on the "Add video" button, it displays the full search view.
      fauxsearchButton.addEventListener("click", () => {
        searchBox.style.display = "flex";
        searchResults.style.display = "flex";
        idSearchContainer.style.width = "auto";
        idSearchContainer.style.height = "auto";
        fauxsearchButton.style.display = "none";
        closeSearchButton.style.display = "flex";
      });
      // When clicking on the times closing button, it hides the full search view and returns to the initial state.
      closeSearchButton.addEventListener("click", () => {
        searchBox.style.display = "none";
        searchResults.style.display = "none";
        idSearchContainer.style.width = "178px";
        idSearchContainer.style.height = "110px";
        fauxsearchButton.style.display = "flex";
        closeSearchButton.style.display = "none";
      });

      /* When the page loads, search for the first video and generate the faux video showcase with its data. */
      // eslint-disable-next-line max-len
      let activityThumbnail = document.querySelector('.activity-thumbnail');
      let thumbOutline = activityThumbnail.querySelector('.thumb-outline');
      let activityTitle = thumbOutline.querySelector('.activity-title').innerText;
      let videoPlaceholderTextTitle = document.querySelector('.video-placeholder-text-title');
      videoPlaceholderTextTitle.innerText = activityTitle;

      let activityImage = thumbOutline.querySelector('.thumb-frame').getAttribute("data-demopicurl");

      let activityLevel = thumbOutline.querySelector('.difficulty-level').innerText;
      let levelNumber = activityLevel.slice(-1);
      let videoPlaceholderTextLabel = document.querySelector('.video-placeholder-text-label');
      videoPlaceholderTextLabel.innerText = levelNumber;


      let difficultyLabel = thumbOutline.querySelector('.difficulty-label').innerText;
      let videoPlaceholderTextLevel = document.querySelector('.video-placeholder-text-level');
      videoPlaceholderTextLevel.innerText = difficultyLabel;

      let activityVideoId = thumbOutline.querySelector('.thumb-frame').getAttribute("data-url");

      let activityDescription = thumbOutline.querySelector('.thumb-frame').getAttribute("description");
      let videoPlaceholderTextDescription = document.querySelector('.video-placeholder-text-description');
      videoPlaceholderTextDescription.innerText = activityDescription;

      let activityTopics = thumbOutline.querySelector('.thumb-frame').getAttribute("topics");
      let videoPlaceholderTextTags = document.querySelector('.video-placeholder-text-tags');
      videoPlaceholderTextTags.innerText = activityTopics;


      let newImage = document.createElement("img");
      newImage.setAttribute("src", activityImage);
      newImage.setAttribute("data-url", activityVideoId);
      newImage.setAttribute("class", "video-placeholder-video-image");

      let bigPlayButton = document.createElement("img");
      bigPlayButton.setAttribute("src", 'pix/big-play-icon.svg');
      bigPlayButton.setAttribute("data-url", activityVideoId);
      bigPlayButton.setAttribute("class", "video-placeholder-video-big-play-button");

      let fauxSocialButtons = document.querySelector('.video-placeholder-text-social');
      fauxSocialButtons.setAttribute("data-url", activityVideoId);

      let VideoPlaceholderTextProgress = document.querySelector('.video-placeholder-text-progress');
      VideoPlaceholderTextProgress.setAttribute("data-url", activityVideoId);

      let videoPlaceholderVideo = document.querySelector('.video-placeholder-video');
      videoPlaceholderVideo.append(bigPlayButton);
      videoPlaceholderVideo.append(newImage);
    }
  };
});