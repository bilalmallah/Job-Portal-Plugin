# Logos

Drop the two brand files in this folder, with exactly these names:

| File | What it is | Where it shows |
| --- | --- | --- |
| `careerhub-logo.png` | The CareerHub product mark | Setup wizard header, WordPress admin menu icon |
| `bm-infinity-logo.png` | BM Infinity Tech Solutions, the maker | Setup wizard footer |

Both are optional. When a file is absent the plugin falls back on its own -
the wizard draws a dashicon tile instead of the product mark, the admin menu
uses `dashicons-groups`, and the footer simply omits the maker's logo. Nothing
breaks and no broken image is ever printed; see `cwcp_brand_logo_file()` in
`includes/helpers.php`.

## Notes

* PNG with a transparent background works best. The CareerHub mark is drawn at
  76x76 in the wizard and 20x20 in the admin menu, so a square source around
  512x512 is plenty - a 1250x1250 file is scaled down by the browser on every
  admin page load.
* Keep the filenames as they are. They are referenced from
  `cwcp_brand_logo_url()` and `cwcp_brand_author_logo_url()`.
