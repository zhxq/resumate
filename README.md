# Resumate - Use JSON-based Templates to Create Personal Homepage

Resumate is a way of creating your personal information (CV, academic, etc.) website easily. If you are a Graduate student who is too lazy or just simply don't know how to write code for your personal academic site, you are at the right place. 

## How to Use Resumate?

You can just download this GitHub repo and change site settings (``settings.json``) and create your pages in ``./definitions/pages`` - you can use the JSON files we have provided for Resumate.io itself as a start. For more information, please read the following sections. We also provide more documentation on Resumate.io.

### Environment

Resumate should run under any environment with PHP7+, with no additional libraries required. We want to keep it simple and stupid (KISS principle), so people with little knowledge with programming and environment setup can use Resumate as pro. 

Many universities provide hosting with limited features, but usually comes with PHP support. Contact your university for more information. This usually comes with URL like https://dept.university.edu/~netid.

### Site Settings

Site settings are configured in ``settings.json``. You can change a wide variaty of things, from the site title to the menu. It also has multi-language support for most of the options.

### Multi-language Support

Multi-language support ships with Resumate and can be used out-of-the-box. Language files are located at ``./definitions/lang``. Don't forget to add your language file in ``settings.json`` to make it work!

### Creating Your Site

Resumate comes with four different types of *visible* definitions, which will be rendered into HTML and seen by your visitor: Pages, Blocks, Templates, and HTML definitions. Consider them as Legos: HTML definitions are just a single Lego piece, Templates as reusable modules like walls with windows and doors, Blocks as a single building containing several different templates, and the Page is something like a campus you want to finally deliver to your visitors.

#### Pages

Pages (located at ``./definitions/pages``) are things that you want to show to your visitors. A page can contain everything: other pages, different blocks, templates, or HTML tags.

#### Blocks

Blocks (located at ``./definitions/blocks``) are similar to pages, but it is for the sanity of your pages, providing modularity to your page. If you have a very long page with different sections, you can consider writing each section into a block, and let the page refer these blocks sequentially. In this case, if you want to change a small part of your page, you can just go to that block and change it.

#### Templates

Templates (located at ``./definitions/templates``) are... templates. This is something you might want to use for several times on a page. In other words, it provides a "feature" that you want people to use. For example: A template for published papers allows people to provide information, including title, authors, journal/conference name, and a link to the paper. If a person want to put 10 papers on his/her page, he/she can just use the template for 10 times.

#### HTML definitions

HTML definitions (located at ``./definitions/html``) are definitions for HTML tags. It tells Resumate how to translate your Resumate JSON files into HTML tags.