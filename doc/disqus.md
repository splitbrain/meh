# Importing Comments from Disqus

Meh provides a built-in [CLI tool](cli.md) to import comments from Disqus, making it easy to migrate your existing comment system.

## Exporting from Disqus

Before you can import comments into Meh, you need to export them from Disqus:

1. Log in to your Disqus admin panel
2. Go to your site's admin section
3. Navigate to the "Community" tab
4. Click on "Export" in the sidebar
5. Request a data export
6. Wait for the export to be prepared (you'll receive an email)
7. Download the XML file

## Importing to Meh

Once you have your Disqus export XML file, you can import it using the `meh` command line tool:

```
./meh disqus path/to/your/disqus-export.xml
```

For multi-site setups, specify the site name:

```
./meh --site blog2 disqus path/to/your/disqus-export.xml
```

## Notes

* Disqus does not export E-Mail addresses, so imported comments will not have an associated E-Mail address.
* It *worked for me*™ but your mileage may vary. You can always import into a new site to test it out before importing into your main site.
