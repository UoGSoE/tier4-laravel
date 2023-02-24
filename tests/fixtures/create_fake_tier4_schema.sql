CREATE TABLE tier4_supervisors(
   id INT PRIMARY KEY     NOT NULL,
   'guid'           CHAR(100)    NOT NULL,
   surname           CHAR(100)    NOT NULL,
   forenames           CHAR(100)    NOT NULL,
   email            CHAR(200)     NOT NULL,
   current CHAR(1) DEFAULT NULL
);

CREATE TABLE tier4_admins(
   id INT PRIMARY KEY     NOT NULL,
   'guid'           CHAR(100)    NOT NULL,
   email            CHAR(200)     NOT NULL
);

CREATE TABLE tier4_students(
   id INT PRIMARY KEY     NOT NULL,
   matric           CHAR(100)    NOT NULL,
   surname           CHAR(100)    NOT NULL,
   forenames           CHAR(100)    NOT NULL,
   email            CHAR(200)     NOT NULL,
   current CHAR(1) DEFAULT NULL,
   notes TEXT DEFAULT NULL,
   supervisor_id INT
);

CREATE TABLE tier4_meetings(
   id INT PRIMARY KEY     NOT NULL,
   supervisor_id INT,
    student_id INT,
    meeting_date DATE
);
