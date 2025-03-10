# Sentiment Analyzer Plugin for WordPress

This plugin analyzes post content for sentiment (positive, negative, neutral) based on configurable keyword lists. It allows displaying sentiment badges on posts and offers a shortcode to filter posts with pagination by sentiment.


## Features
- **Sentiment Analysis**: Automatically analyze post content on plugin activation and save posts using keyword lists that is configured by admin settings page.
- **Sentiment Filter Title**: Display a sentiment filter title avobe the filter container.
- **Sentiment Badge**: Display a sentiment badge on posts.
- **Shortcode**: Use `[sentiment_filter sentiment="positive|negative|neutral"]` to filter posts by sentiment with pagination.
- **Admin Settings**: Configure keyword lists for each sentiment and transient caches can be cleared.

## Shortcode Attributes
- **sentiment**: Sentiment attribute accept following sentiments positive, negative and neutral. Multiple sentiments values can be give which is separeted by separeted by "|".
- **title**: Title attribute accept any characters.
- **display**: Display attribute accept only 2 values they are list and grid.
- **posts_per_page**: This attribute will set posts limit per page.



## Installation
1. Upload the plugin files to your WordPress plugins directory.
2. Activate the plugin via the WordPress admin panel.

## Usage
- Sentiment analysis is done automatically on plugin activation and post save.
- Shortcode `[sentiment_filter sentiment="positive|negative|neutral"]` can be used to display posts filtered by sentiment.
- Cache can be cleared from `Sentiment Analyzer` admin menu page.

## License
GPL-2.0
