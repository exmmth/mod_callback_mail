# "Mail Callback" Joomla module

**v1.1.0**

**Joomla 3.6 or later**

**PHP 5.6 or later**

Feedback module that sends messages to e-mail.

### Description

The list of fields is empty by default, so you should fill it in, then save it. Supported field types: text, email, url, tel, password, textarea, select, checkbox, radio. Each field must have a name (to form a form) and a name (to identify the field in the message in the telegram).

You can also specify a title for the letter, if the title is not specified, a string of the form "Message from site {url}" is used.

The module supports overridable email templates. Templates are located in the /layouts folder.

The module does not contain embedded CSS and JS, but the layout of the form template is presented in three versions: bootstrap2 (default), bootstrap3/4, uikit2, uikit3.

The message about the successful sending of the post or error is displayed through the standard Joomla system messags.
