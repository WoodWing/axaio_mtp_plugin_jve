Tripolis Integration for Enterprise 6.1.x and 7
Tripolis Dialogue plug-in

Make sure that your configuration is correct:
- check the settings in config.php in this directory
- create a channel to publish to Tripolis Dialogue
  - enter name
  - in the description enter "DIRECTEMAIL" to publish with direct emails,
    enter "NEWSLETTER=<newlettertype>" to publish a newsletter. "<newslettertype>"
    must be replaced with the Tripolis Dialogue newletter type name (not label).
    Add ",DIRECTSENT" to send the mailing directly after you've published it else
    it only will generate content for Tripolis Dialogue.
    e.g.:
    DIRECTEMAIL
    DIRECTEMAIL,DIRECTSENT
    NEWSLETTER=monthly
    NEWSLETTER=monthly,DIRECTSENT
  - Publication Channel Type: sms
  - Publish System: Publish to Tripolis
- create issues for each Tripolis contact group, use the Group Name (not Group Label) as issue name
  To send mail to a group, publish a dossier to the right issue and the mail will be sent to that group
- make sure the newsletter sections in Tripolis Dialogue are same as the Enterprise categories

MAPPING

- Tripolis Article "title" = Enterprise Article Name
- Tripolis Article "body" = Enterprise Article Content
- Tripolis Article "bodytext" = Enterprise Article Content without HTML tags

LIMITATIONS

- Images in newsletters are not supported
- Article plain text is just the HTML content without the HTML tags, nothing more.