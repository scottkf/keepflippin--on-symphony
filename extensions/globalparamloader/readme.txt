Global Parameter Loader
-------------------------------------------------------------------------------

Version: 1.1.6
Author: Carsten de Vries <carsten@vrieswerk.nl>
Build Date: 3 May 2009
Github Repository: http://github.com/carsten/globalparamloader/tree/master
Requirements: Symphony 2

Allows you to add sets of parameters to Symphony's parameter pool while 
excluding specified pages. Furthermore, valid PHP code is parsed before adding 
the parameter to the parameter pool.

Warning: safe evaluation has not been added yet. Do not enable this extension 
if you are not absolutely sure that your Symphony installation is secure.


Installation
-------------------------------------------------------------------------------

1.  Upload the 'globalparamloader' folder in this archive to your Symphony
  'extensions' folder.

2.  Enable it by selecting the "Global Parameter Loader", choose Enable from 
  the with-selected menu, then click Apply.


Usage
-------------------------------------------------------------------------------

1.  View the Blueprints > Global Parameters page to add, edit, and remove 
  Parameter Sets.

2.  Click the create new button to create your first Parameter Set. Give the 
  Parameter Set a name.

3.  Click Add item to add a parameter. At least one parameter must be defined 
  for each set, and each parameter must have a name.

4.  Choose any pages you wish to exclude from this Parameter Set.

5.  Click the Create Set button. Now, go see your newly added parameters at 
  your pages in Parameter debug mode (add ?debug=params to the page URL).


Example A: Add user IP address to the parameter pool
-------------------------------------------------------------------------------

1.	Add a parameter with parameter value:
  return ($_SERVER['REMOTE_ADDR']);


Example B: Add domainname and extension to the parameter pool
-------------------------------------------------------------------------------

1.	Add a parameter with parameter value:
  return array_pop(explode('.', $_SERVER['SERVER_NAME'], 2));
	

Acknowledgments
-------------------------------------------------------------------------------

The Symphony development team and all contributors for such an amazing 
content management system. Rowan Lewis for both his emailtemplatefilter and
globalresourceloader extensions, on which this extension is based. Marcin 
Konicki for telling me to reinstall this extension to make it work.
