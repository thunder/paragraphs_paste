# Paragraphs Paste

This module provides functionality to create various paragraph entities by pasting content into an area of a content form.

It determines the paragraph type created based on the content provided, for example, a youtube link triggers creation of a paragraph suitable to hold a youtube or video media entity.

For custom paragraph types using multiple fields, create your own ParagraphsPastePlugin plugins.

For processing text using the [textile](https://textile-lang.com) parser, require the php lib: `composer require netcarver/textile`.
