# OrderUploadBundle

Kimai plugin for projects with the following features:

1. **Order field** ("Bestellung") in the "Create Project" and "Edit Project" dialogs: upload a document.
2. **Order box** on the project detail page: download and remove.
3. **Attachments box** on the project detail page: upload and manage multiple additional files.

## Installation

The plugin directory must be named exactly `OrderUploadBundle` and must be located directly in `var/plugins/`.

From a release (recommended): download `OrderUploadBundle-<version>.zip` from the Releases page. It already contains the `OrderUploadBundle` directory.

```
cd /path/to/kimai/var/plugins/
unzip OrderUploadBundle-<version>.zip
```

Or with Git, with the target directory given explicitly:

```
cd /path/to/kimai/var/plugins/
git clone https://github.com/s3ppo/kimai_orderuploadbundle.git OrderUploadBundle
```

Then reload the cache:

```
cd /path/to/kimai
bin/console kimai:reload --env=prod
```

`var/` must be writable by the web server user. No database migration is required.

Docker: `var/data` must be persisted as a volume.

To update, delete the `OrderUploadBundle` directory, install the new version as described above, and reload the cache.

## Behavior

- The order is a Kimai meta field ("bestellung") on the project. Only the file identifier is stored in the meta table; the file itself is located in `var/data/project_attachments/bestellung_dokumente/`.
- Additional attachments are stored in `var/data/project_attachments/<project-id>/`.
- Both are stored outside the web root; downloads are always served as attachments.
- Viewing and downloading: anyone with permission to view the project (permission `view`). Uploading, replacing, and deleting: anyone with permission to edit the project (permission `edit`).
- Maximum 25 MB per file (`AttachmentStorage::MAX_BYTES`), plus the PHP limits `upload_max_filesize` and `post_max_size`.
- When editing without uploading a new file, the order document remains unchanged.

## Limitations

- Each project can have exactly one order document. When replaced, the old file remains on disk. Deletion via the order box removes it.
- When deleting a project, the associated files remain on disk.
- If project saving fails after upload, the file must be selected again in the dialog.
- The uploader's name is not recorded.
- The directories should be included in backups (`var/data`).

## Compatibility

Developed against the Kimai 2.72.0 sources and Symfony 6.4.49; tested with Kimai 2.71.0 (Docker). Other versions have not been tested.