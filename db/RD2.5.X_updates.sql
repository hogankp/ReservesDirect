-- new column material_type for items
ALTER TABLE `items` ADD `material_type` varchar(255) DEFAULT NULL;
ALTER TABLE `items` ADD `publisher` varchar(255) DEFAULT NULL;
ALTER TABLE `items` ADD `availability` tinyint(1) DEFAULT NULL;

-- pages_times_range will contain the range of pages or time interval requested.
ALTER TABLE `items` ADD `pages_times_range` varchar(255) DEFAULT NULL;
-- pages_times_total will contain the total pages or amount of time in the resource.
ALTER TABLE `items` ADD `pages_times_total` varchar(255) DEFAULT NULL;
-- pages_times_used will contain the number of pages or amount of time requested.
ALTER TABLE `items` ADD `pages_times_used` varchar(255) DEFAULT NULL;

-- set images to have an image icon and launch with a browser.
INSERT INTO mimetypes (mimetype_id, mimetype, helper_app_url, helper_app_name, helper_app_icon) VALUES (8, 'image/jpeg', NULL, 'Image', 'images/doc_type_icons/doctype-image.gif');
UPDATE mimetypes set helper_app_icon = 'images/doc_type_icons/doctype-excel.gif' where helper_app_name = 'Microsoft Excel';
UPDATE mimetypes set helper_app_icon = 'images/doc_type_icons/doctype-ppt.gif' where helper_app_name = 'Microsoft Powerpoint';

-- set mimetype extensions.
UPDATE mimetype_extensions set file_extension = 'xls' where id = 5 and file_extension = 'xcl';
INSERT INTO mimetype_extensions (mimetype_id, file_extension) VALUES (8, 'jpeg');
INSERT INTO mimetype_extensions (mimetype_id, file_extension) VALUES (8, 'jpg');
INSERT INTO mimetype_extensions (mimetype_id, file_extension) VALUES (8, 'gif');

-- add new field for copyright status in the reserves table for queue display.
ALTER TABLE `reserves` ADD `copyright_status` set('NEW', 'PENDING', 'ACCEPTED', 'DENIED') NOT NULL DEFAULT 'NEW' COMMENT 'Do we need/have permission from pub?';

-- create rightsholder info table.
CREATE TABLE rightsholders (
  ISBN varchar(13) NOT NULL,
  name varchar(255),
  contact_name varchar(255),
  contact_email varchar(255),
  fax varchar(50),
  post_address text,
  rights_url varchar(255),
  policy_limit varchar(255),
  PRIMARY KEY (ISBN)
);

-- new view for calculating, given a course instance and an isbn, what
-- total percentage of that isbn have we stored for that course (to the
-- degree that we have that information).
CREATE VIEW course_book_usage AS
  SELECT i.ISBN, ci.course_instance_id,
         SUM(CAST(i.pages_times_used AS DECIMAL) /
             CAST(i.pages_times_total AS DECIMAL)) * 100 AS percent_used
  FROM items i
    JOIN reserves r ON r.item_id = i.item_id
    JOIN course_instances ci ON r.course_instance_id = ci.course_instance_id
  WHERE i.ISBN IS NOT NULL AND
        i.ISBN <> '0' AND
        i.pages_times_used IS NOT NULL AND
        i.pages_times_total IS NOT NULL
  GROUP BY i.ISBN, ci.course_instance_id;

