# OrderUploadBundle

Kimai plugin for projects with the following features:

1. **Order field** in the "Create Project" and "Edit Project" dialogs: upload a document.
2. **Order box** on the project detail page: download and remove.
3. **Attachments box** on the project detail page: upload and manage multiple additional files.

## Installation

```
cd /path/to/kimai/var/plugins/
unzip OrderUploadBundle.zip
cd /path/to/kimai
bin/console kimai:reload --env=prod
```

The directory must be named exactly `OrderUploadBundle`, and `var/` must be writable by the web server user. No database migration is required.

Docker: `var/data` must be persisted as a volume.

## Behavior

- The order is a Kimai meta field ("bestellung") on the project. Only the file identifier is stored in the meta table; the file itself is located in `var/data/project_attachments/bestellung_dokumente/`.
- Additional attachments are stored in `var/data/project_attachments/<project-id>/`.
- Both are stored outside the web root; downloads are always served as attachments.
- Viewing and downloading: anyone with permission to view the project (permission `view`). Uploading, replacing, and deleting: anyone with permission to edit the project (permission `edit`).
- Maximum 25 MB per file (`AttachmentStorage::MAX_BYTES`), plus PHP configuration limits for `upload_max_filesize` and `post_max_size`.
- When editing without uploading a new file, the order document remains unchanged.

## Limitations

- Each project can have exactly one order document. When replaced, the old file remains on disk. Deletion via the order box removes it.
- When deleting a project, the associated files remain on disk.
- If project saving fails after upload, the file must be selected again in the dialog.
- The uploader's name is not recorded.
- The directories should be included in backups (`var/data`).

Developed against Kimai 2.72.0 sources and Symfony 6.4.49; tested with Kimai 2.71.0 (Docker).
