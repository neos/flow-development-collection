# Configuration file for the Sphinx documentation builder.
#
# This file only contains a selection of the most common options. For a full
# list see the documentation:
# https://www.sphinx-doc.org/en/master/usage/configuration.html

# -- Path setup --------------------------------------------------------------

# If extensions (or modules to document with autodoc) are in another directory,
# add these directories to sys.path here. If the directory is relative to the
# documentation root, use os.path.abspath to make it absolute, like shown here.
#
# import os
# import sys
# sys.path.insert(0, os.path.abspath('.'))

import os
import sys

# -- Project information -----------------------------------------------------

project = 'Flow Framework'
copyright = '2006 and onwards by the authors'
author = 'Neos Team and Contributors'

# The short X.Y version.
version = 'dev-6.3'
# The full version, including alpha/beta/rc tags.
release = 'dev-6.3.x'


# -- General configuration ---------------------------------------------------

# Add any Sphinx extension module names here, as strings. They can be
# extensions coming with Sphinx (named 'sphinx.ext.*') or your custom
# ones.
extensions = [
    'sphinx_rtd_theme',
    'sphinx.ext.intersphinx',
    'sphinx.ext.todo',
    'sphinx.ext.ifconfig'
]

# Add any paths that contain templates here, relative to this directory.
templates_path = ['_templates']

# List of patterns, relative to source directory, that match files and
# directories to ignore when looking for source files.
# This pattern also affects html_static_path and html_extra_path.
exclude_patterns = ['_build', 'Thumbs.db', '.DS_Store']





# The suffix(es) of source filenames.
# You can specify multiple suffix as a list of string:
# source_suffix = ['.rst', '.md']
source_suffix = '.rst'
# The master toctree document.
master_doc = 'index'
# The name of the Pygments (syntax highlighting) style to use.
pygments_style = 'sphinx'
# If true, `todo` and `todoList` produce output, else they produce nothing.
todo_include_todos = False





# -- Options for HTML output -------------------------------------------------

# The theme to use for HTML and HTML Help pages.  See the documentation for
# a list of builtin themes.
#
html_theme = 'sphinx_rtd_theme'

# Add any paths that contain custom static files (such as style sheets) here,
# relative to this directory. They are copied after the builtin static files,
# so a file named "default.css" will overwrite the builtin "default.css".
html_static_path = ['_static']

# Output file base name for HTML help builder.
htmlhelp_basename = 'FlowFrameworkdoc'

# on_rtd is whether we are on readthedocs.org, this line of code grabbed from docs.readthedocs.org
# on_rtd = os.environ.get('READTHEDOCS', None) == 'True'
# if not on_rtd:  # only import and set the theme if we're building docs locally
#     import sphinx_rtd_theme
#     html_theme = 'sphinx_rtd_theme'
#     html_theme_path = [sphinx_rtd_theme.get_html_theme_path()]

html_theme_options = {
  'prev_next_buttons_location': 'both',
  'style_external_links': True
}

# -- Additional options ---------------------------------------------------

# Example configuration for intersphinx: refer to the Python standard library.
intersphinx_mapping = {
    'neos': ('https://neos.readthedocs.io/en/stable', None),
}

# load PhpLexer
from sphinx.highlighting import lexers
from pygments.lexers.web import PhpLexer

# enable highlighting for PHP code not between <?php ... ?> by default
lexers['php'] = PhpLexer(startinline=True)
lexers['php-annotations'] = PhpLexer(startinline=True)

# Use PHP syntax highlighting in code examples by default
highlight_language='php'
