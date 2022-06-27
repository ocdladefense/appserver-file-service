# appserver-file-upload
Upload a file as an [blank].

Previously referred to as sObject Attachment sObject Documents.


## Test routes
[Test route URL](/file/upload/form/a2C05000000qFiyEAE) includes the Salesforce record Id to use when *updating* a ContentDocument for a given Salesforce record.


## Related Salesforce SObjects
### ContentDocument
- https://developer.salesforce.com/docs/atlas.en-us.object_reference.meta/object_reference/sforce_api_objects_contentdocument.htm

### ContentDocumentLink
 - https://developer.salesforce.com/docs/atlas.en-us.object_reference.meta/object_reference/sforce_api_objects_contentdocumentlink.htm

### ContentVersion
- https://developer.salesforce.com/docs/atlas.en-us.object_reference.meta/object_reference/sforce_api_objects_contentversion.htm



#### Testing Script
1. While not logged in click on the "Upload Files" and "My Shared Files" and make sure you are getting the access denied message.

2. After logging in, click on the links again to make sure you have access.

3. Upload a file using the file upload form with none of the sharing options selected, and make sure that the "ContentDocumentLink" for the file has the current contact's ContactId for the LinkedEntityId field.

4. Upload another file with the sharing options selected.  Make sure that the ContentDocumentLink(s) have the correct LinkedEnityIds.

### Testing Script Part 2
1. The expectation is that if I upload a file while sharing with my contact and a committee I will see two ContentDocumentLinks with the same ContentDocumentId, and the same LinkedEntityId.  I expect to see one ContentDocumentLink with matching ContentDocumentId and LinkedEntityId for each entity I shared it with.

2. 