News Block README
==================
 
 
Changes to spec
---------------

DB table block_news_messages: 
'date' and 'repeat' are reserved words in some implementations,
so in the interests of DB cross-compatibility they have been changed to 'messagedate',
 'messagerepeat'. 
For consistency, the related 'visible' column has also been changed to 'messagevisible'

Config:
Added $CFG->maxitemsperfeed parameter to limit items read per attempt. 
If not set or set to 0, then all are read

Scripts:
hide/show,delete incorporated in message
