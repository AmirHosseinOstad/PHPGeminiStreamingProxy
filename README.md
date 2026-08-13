**🤖 PHP Gemini Streaming Proxy**
A lightweight, optimized, and powerful PHP script for communicating with Google Gemini AI models in real-time streaming. This script is specifically designed for use as a backend in bots, chatbots, and web applications.

✨ Highlights
🔄 Round-Robin: Ability to use multiple API keys in a round robin fashion with secure file management to prevent concurrent requests from interfering.
⚡ Real-time Streaming: Disable output buffering to display AI responses in real time (suitable for chat UIs).
🧠 Smart Model Selection: Automatically selects the right model based on user needs (regular chat, deep thinking, web search, or image processing).
🖼️ Image Support: Ability to send and analyze images along with text.
🏷️ Chat Title Generation: Lightweight mode support for quickly generating titles for conversations.
🛡️ Advanced error handling: Detect connection errors, HTTP errors, and Google safety filters.

⚙️ Prerequisites
PHP version 8.0 or higher
CURL library enabled in PHP
Internet access and at least one API key from Google AI Studio

📥 Installation and setup
1. Place the script file (for example, proxy.php) on your server or host.
2. Enter your API keys in the $apiKeys array at the beginning of the code:

```
$apiKeys = [
'AIzaSyYourApiKeyHere1...',
'AIzaSyYourApiKeyHere2...',
// Add as many keys as you want
];
```

3. Make sure you have Write Permission to create the api_key_index.txt file next to the script folder.

🚀 How to use (send a request)
This script receives requests as POST and in JSON format.
Sample request structure (JSON):
```
{
"messages": [
{ "role": "user", "text": "Hi, how can I learn Python?" }
],
"think": false,
"search": true,
"hasImage": false
}
```

Parameters passed:
messages: An array of previous chat messages (user or model).
text: (optional) Plain text to send individually (if not using the messages structure).
think: (boolean) Enable deep thinking mode for the model (if needed).
search: (boolean) Enable web search capability in Google.
hasImage: (boolean) Setting for image processing.
image: (array - if hasImage: true) containing mimeType and data (in Base64).
forTitle: (boolean) Use the lightest model to generate the chat title.

📄 License
This project is released under the MIT License. You can use it freely in your personal and commercial projects.

Developed with ❤️ for use in your cool projects. If you have any suggestions, contributions in the form of a Pull Request are always welcome!
