---
title: Push Publishing

access:
    admin.publisher: true # TODO: make this role a thing
    admin.super: true
    admin.login: true

forms:
    publication-form:
        action: /publish/ajax/commit.json
        id: publish-form
        fields:
            -   name: message
                label: Changes description
                placeholder: 'e.g. "Updated welcome text"'
                help: Brief message to describe these changes
                type: text
                validate:
                    required: true
        buttons:
            -   type: submit
                value: Publish
        process:
            -   message: Changes published!

# TODO: more details in content as functionality is added e.g. checkboxes for each file
---
These are the changes you have made that you can publish to the live site.