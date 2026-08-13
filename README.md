# 🤖 PHP Gemini Streaming Proxy

A lightweight, optimized, and powerful PHP script for communicating with Google Gemini AI models via real-time SSE streaming. Specifically designed for backends in telegram bots, web apps, and modern chat interfaces.

---

## ✨ Features

* **🔄 Key Rotation (Round-Robin):** Rotates through multiple API keys with secure file locking (`LOCK_EX`) to handle concurrent requests smoothly.
* **⚡ Real-time SSE Streaming:** Flushes buffers instantly to push responses line-by-line to your frontend.
* **🧠 Dynamic Model Routing:** Auto-selects optimal models based on payload requirements (standard chat, deep reasoning, web grounding, or vision).
* **🖼️ Multimodal Support:** Accepts base64 encoded images for direct visual analysis alongside prompt instructions.
* **🏷️ Utility Mode:** Lightweight mode optimized for low-latency chat title generation.
* **🛡️ Robust Error Handling:** Filters and reports connection failures, HTTP errors, and safety threshold triggers gracefully.

---

## ⚙️ Requirements

* **PHP:** 8.0 or higher
* **Extensions:** `curl`, `json`
* **Permissions:** Write access in the script directory (for tracking key index states)
* **API Key:** Active key from Google AI Studio

---

## 📥 Quick Setup

1. Copy `proxy.php` to your application directory.
2. Configure your API keys array at the top of the script:

```php
$apiKeys = [
    'AIzaSyYourApiKeyHere1...',
    'AIzaSyYourApiKeyHere2...',
];

```

3. Ensure directory permissions allow file creation for state persistence (`api_key_index.txt`).

---

## 🚀 API Specification

Send POST requests with a JSON payload to the proxy endpoint.

### Request Payload Format

```json
{
  "messages": [
    { "role": "user", "text": "Hi, how can I learn Python?" }
  ],
  "think": false,
  "search": true,
  "hasImage": false
}

```

### Parameters Reference

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| `messages` | `array` | Optional* | Sequential list of historical chat messages (`role`, `text`). |
| `text` | `string` | Optional* | Direct text prompt if not sending full conversation history. |
| `think` | `boolean` | Optional | Enables deep thinking/reasoning mode. |
| `search` | `boolean` | Optional | Enables Google Web Search grounding. |
| `hasImage` | `boolean` | Optional | Set to `true` when including image data. |
| `image` | `object` | Optional | Contains `mimeType` and `data` (Base64 string). |
| `forTitle` | `boolean` | Optional | Routes to a fast, low-cost model for title generation. |

---

## 📄 License

Distributed under the MIT License. Free for personal and commercial usage.

---

Crafted with ❤️ for modern developer tools. Contributions and Pull Requests are welcome!
