# Web-Chunk-Uploader

This script allows users to upload large files by splitting them into smaller chunks, which is particularly useful when dealing with PHP servers that have strict file size restrictions.

## Requirements

- PHP 7.0 or higher
- Web server with PHP support
- Write permissions of the server

## Installation

1. Download the script and place it in your web directory or integrate it into your target file.
2. Create two directories in your project:
   - `uploads/` (for final files)
   - `temp/` (for storing chunks)
3. Ensure both directories have write permissions

## Customization

You can modify the following variables to suit your needs:
- `$uploadDir`: Change the final file directory
- `$tempDir`: Change the temporary chunk directory
- `chunkSize`: Adjust the chunk size in the JavaScript part
