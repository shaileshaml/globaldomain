# globaldomain
Round Cube Plugin for users allow selected domains to sent emails default allow same domain 

Present all entry into database manually 

tested in mysql database 

Add email id and allow domains name

like default domain %d 

for all domain all

for selected domain %d,gmail.com,hotmail.com
sample data

test@mydomain.com, %d, 1, now(), now() // only domain 
test1@mydomain.com, all, 1, now(), now() // allow all domain
test2@mydomain.com, %d,gmail.com,hotmail.com, 1, now(), now() // allow mydomain.com, gmail.com, hotmail.com -- for remain domain get error SMTP Error: Unable to parse recipients list.
