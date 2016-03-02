# files_memcache #

Minimal filecache implementation using memcache

## Not intended for production use ##

Besides being largely untested there are multiple things with this cache implementation that make it unsuitable for regular production use.

- file and storage id's can conflict with id's from non-local storage backends
- search is not implemented
- probably more

## Cache implementation ##

This replaces owncloud's internal cache implementation for local storage backends with a minimal memcache based one.

The only things stored in the memcache are fileid -> path mappings and etags, everything else is always read directly from the storage backend
