SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM ALL_SocialObject;

DELETE FROM DIR_Actor;
INSERT INTO ALL_SocialObject(soid,kind) VALUES
  ( 1,'User'),(40,'User'),(50,'User'),(60,'User');
INSERT INTO DIR_Actor(soid,name,owner) VALUES
  ( 1,'lisa',NULL),
  (40,'barney',NULL),
  (50,'maria',NULL),
  (60,'ahmed',NULL);

INSERT INTO ALL_SocialObject(soid,kind) VALUES
  (20,'Group'),(30,'Group');
INSERT INTO DIR_Actor(soid,name,owner) VALUES
  (20,'acme-corp',1),
  (30,'sw-dev-dept',40);

DELETE FROM DIR_Membership;
INSERT INTO DIR_Membership(groop,member) VALUES
  (20,30),
  (30,50),
  (30,60);

DELETE FROM DIR_Perspective;
INSERT INTO DIR_Perspective(perspective_url,owner) VALUES
  ('Perspective/2',1),
  ('Perspective/21',20),
  ('Perspective/31',30),
  ('http://localhost:8080/social-server-2/resource/Perspective/51',50),
  ('Perspective/61',60);
  
  
  
  
  
  
  
    
  
  
  
  
  
  
  
  
DELETE FROM REP_Perspective;
INSERT INTO ALL_SocialObject(soid,kind) VALUES
  (2,'Perspective'),(21,'Perspective'),(31,'Perspective'),(51,'Perspective'),(61,'Perspective');
INSERT INTO REP_Perspective(soid,name,owner) VALUES
  (2,'ProjectTracker 1.0','1'),
  (21,'project.acme.com','20'),
  (31,'SoftwareDevelopment','30'),
  (51,'~maria','50'),
  (61,'~ahmed','60');

DELETE FROM REP_ClassFragment;
INSERT INTO ALL_SocialObject(soid,kind) VALUES
  ( 3,'ClassFragment'), ( 4,'ClassFragment'), ( 5,'ClassFragment'), (22,'ClassFragment'),
  (32,'ClassFragment'), (33,'ClassFragment'), (52,'ClassFragment'), (62,'ClassFragment'),
  (63,'ClassFragment');
INSERT INTO REP_ClassFragment(soid,perspective,name) VALUES
  ( 3,2,'Project'),
  ( 4,2,'Task'),
  ( 5,2,'Resource'),
  (22,21,'*Project'),
  (32,31,'*Task'),
  (33,31,'Product'),
  (52,51,'*Project'),
  (62,61,'*Task'),
  (63,61,'Platform');

DELETE FROM REP_Attribute;
SET FOREIGN_KEY_CHECKS = 0;
INSERT INTO REP_Attribute(soid,classFragment,name,type,positionInLabel) VALUES
  ( 6, 3,'name','A(S)',0),
  (11, 3,'tasks','C(http://localhost:8080/social-server-2/resource/ClassFragment/4,10)',-1),

  ( 7, 4,'name','A(S)',0),
  ( 8, 4,'state','E(WAITING,DONE)',-1),
  (10, 4,'project','R(ClassFragment/3)',-1),
  (13, 4,'resources','C(ClassFragment/5,12)',-1),

  ( 9, 5,'name','A(S)',0),
  (12, 5,'task','R(ClassFragment/4)',-1),

  (23,22,'type','E(Feasibility,Prototype,Industrialization,Optimization,Other)',-1),
  
  (34,32,'type','E(Requirements,Specification,Design,Implementation,Qualification)',-1),
  (35,32,'plannedEffort','A(I)',-1),
  (36,32,'actualEffort','A(I)',-1),
  (38,32,'product','R(ClassFragment/33)',-1),

  (37,33,'name','A(S)',0),
  (39,33,'tasks','C(ClassFragment/32,38)',-1),

  (53,52,'risk','A(I)',-1),
  
  (64,62,'priority','A(I)',-1),
  (66,62,'platform','R(ClassFragment/63)',-1),
  
  (65,63,'name','A(S)',0),
  (67,63,'tasks','C(ClassFragment/62,66)',-1);
INSERT INTO ALL_SocialObject(soid,kind)
  SELECT soid,'Attribute' FROM REP_Attribute;

DELETE FROM REP_ClassExtension;
INSERT INTO REP_ClassExtension(child,parent) VALUES
  (22, 'ClassFragment/3'),
  (32, 'ClassFragment/4'),
  (52, 'ClassFragment/3'),
  (62, 'ClassFragment/4');

DELETE FROM REP_ImportDeclaration;
INSERT INTO REP_ImportDeclaration(perspective,classFragment,import) VALUES
  (21,'ClassFragment/3',true),(21,'ClassFragment/4',true),(21,'ClassFragment/5',true),
  (31,'ClassFragment/5',false),
  (51,'ClassFragment/4',false),
  (61,'ClassFragment/5',true);


  
  
  
  
SET FOREIGN_KEY_CHECKS = 1;

DELETE FROM REP_Value;
DELETE FROM REP_InstanceFragment;

INSERT INTO ALL_SocialObject(soid) VALUES
  (1000),(1001),
  (10001),(10002),(10003),(10004),
  (100011),(100012),(100013),
  (2000),(2001),
  (3000),(3001);
UPDATE  ALL_SocialObject
  SET   kind='InstanceFragment'
  WHERE kind='';

INSERT INTO REP_InstanceFragment (soid,classFragment) VALUES
  (1000,3),(1000,22),(1000,52),
  (1001,3),(1001,22),(1001,52),

  (10001,4),(10001,32),(10001,62),
  (10002,4),(10002,32),
  (10003,4),(10003,62),
  (10004,4),(10004,32),(10004,62),

  (100011,5),
  (100012,5),
  (100013,5),

  (2000,33),
  (2001,33),

  (3000,63),
  (3001,63);

/*
INSERT INTO Value (soid,attribute,value)
  SELECT Fragment.soid,Attribute.soid,'X')
    FROM Fragment,Class,Attribute
    WHERE Fragment.class=Class.soid
      AND Attribute.class=Class.soid;
*/
INSERT INTO REP_Value (soid,attribute,value) VALUES
(1000, 6, 'High-performance electrical engine'),
(1000, 23, 'Prototype'),
(1000, 53, '40%'),
(1001, 6, 'Lobbying for stronger emission control regulations'),
(1001, 23, 'Other'),
(1001, 53, '80%'),

(2000, 37, 'Electrical sports car'),
(2001, 37, 'Electrical motorcycle'),

(3000, 65, 'RTLinux'),
(3001, 65, 'Other'),

(10001, 7, 'Reverse-engineer competition engines'),
(10001, 8, 'DONE'),
(10001, 10, 'localhost:80/Fragment/1000'),
(10001, 34, 'Requirements'),
(10001, 35, '100'),
(10001, 36, '108'),
(10001, 38, 'localhost:80/Fragment/2001'),
(10001, 64, '10'),

(10002, 7, 'Design engine'),
(10002, 8, 'WAITING'),
(10002, 10, 'localhost:80/Fragment/1000'),
(10002, 34, 'Design'),
(10002, 35, '50'),
(10002, 36, '0'),
(10002, 38, 'localhost:80/Fragment/2001'),

(10003, 7, 'Qualify engine'),
(10003, 8, 'WAITING'),
(10003, 10, 'localhost:80/Fragment/1000'),
(10003, 64, '8'),
(10003, 66, 'localhost:80/Fragment/3000'),

(10004, 7, 'Road tests'),
(10004, 8, 'WAITING'),
(10004, 10, 'localhost:80/Fragment/1000'),
(10004, 34, 'Qualification'),
(10004, 35, '80'),
(10004, 36, '0'),
(10004, 38, 'localhost:80/Fragment/2000'),
(10004, 64, '6'),
(10004, 66, 'localhost:80/Fragment/3000'),

(100011, 9, 'Annabelle'),
(100011,12, 'localhost:80/Fragment/10001'),
(100012, 9, 'Johanna'),
(100012,12, 'localhost:80/Fragment/10002'),
(100013, 9, 'Ruben'),
(100013,12, 'localhost:80/Fragment/10002');
