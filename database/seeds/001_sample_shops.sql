-- Sample shops for LOCAL and STAGING only. Never run on production.
-- (scripts/seed.php refuses when APP_ENV=production)

DELETE FROM shops WHERE is_sample=1;

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('pondy','Mission Street Wheels','Mission Street',11.9345,79.8285,'+91 90000 00001','07:00','21:00',4.6,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Honda Activa 6G',350,60);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','TVS Jupiter',350,60);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Geared','Bajaj Pulsar 150',500,80);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('pondy','French Quarter Rides','White Town',11.9325,79.8335,'+91 90000 00002','08:00','20:00',4.8,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Royal Enfield','Classic 350',1100,0);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Royal Enfield','Himalayan 450',1600,0);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Suzuki Access 125',400,70);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('pondy','Bus Stand Bike Point','New Bus Stand',11.9385,79.817,'+91 90000 00003','06:00','22:00',4.2,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Honda Dio',300,50);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Honda Activa 5G',300,50);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Geared','Hero Splendor',300,50);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Geared','Honda Shine',350,60);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('pondy','Station Road Rentals','Railway Station',11.9275,79.8285,'+91 90000 00004','05:30','22:30',4.4,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','TVS Ntorq',400,70);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Electric','Ather 450X',500,90);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Geared','Yamaha FZ',550,90);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('pondy','Auroville Cycle & Scoot','Auroville',12.006,79.812,'+91 90000 00005','07:00','19:00',4.7,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Cycle','Hercules Roadeo',120,20);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Cycle','Gear cycle',180,30);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Electric','Ola S1',450,80);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Honda Activa 6G',380,60);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('pondy','Serenity Shore Rentals','Kottakuppam / Serenity Beach',11.963,79.837,'+91 90000 00006','08:00','20:00',4.3,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Suzuki Access 125',380,60);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Royal Enfield','Bullet 350',1000,0);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('pondy','Green Volt EV Rentals','MG Road',11.9365,79.8255,'+91 90000 00007','09:00','21:00',4.5,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Electric','Ather Rizta',480,85);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Electric','TVS iQube',450,80);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Electric','Bajaj Chetak',450,80);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('pondy','Lawspet Bike Hub','Lawspet',11.958,79.818,'+91 90000 00008','08:00','20:30',4.1,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Honda Dio',280,50);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Geared','Bajaj Platina',280,45);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('pondy','Muthialpet Motors','Muthialpet',11.946,79.83,'+91 90000 00009','07:30','21:00',4,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Geared','KTM Duke 200',1200,0);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Royal Enfield','Hunter 350',950,0);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','TVS Jupiter',330,55);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('pondy','Ariyankuppam Beach Rides','Ariyankuppam',11.905,79.817,'+91 90000 00010','08:00','19:00',4.2,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Honda Activa 6G',330,55);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Cycle','City cycle',100,20);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('chennai','Marina Ride Co.','Triplicane',13.055,80.274,'+91 90000 00011','07:00','22:00',4.4,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Honda Activa 6G',450,70);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Royal Enfield','Classic 350',1300,0);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('chennai','T Nagar Two-Wheelers','T. Nagar',13.04,80.236,'+91 90000 00012','08:00','21:00',4.1,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','TVS Ntorq',480,80);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Geared','Yamaha R15',1100,0);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('chennai','Besant Nagar EV Hub','Besant Nagar',13.002,80.264,'+91 90000 00013','09:00','21:00',4.6,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Electric','Ather 450X',550,90);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Cycle','Gear cycle',200,35);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('chennai','Central Station Bikes','Park Town',13.082,80.272,'+91 90000 00014','05:00','23:00',4,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Honda Dio',400,65);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Geared','Honda Shine',420,65);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('blr','Koramangala Moto Rentals','Koramangala',12.934,77.626,'+91 90000 00015','07:00','22:00',4.5,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Royal Enfield','Himalayan 450',1800,0);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Geared','KTM Duke 390',1900,0);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','TVS Ntorq',500,80);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('blr','Indiranagar Scoot Stop','Indiranagar',12.979,77.64,'+91 90000 00016','08:00','21:00',4.3,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Honda Activa 6G',450,70);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Electric','Ola S1 Pro',600,95);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('blr','Majestic Bike Point','Majestic',12.978,77.573,'+91 90000 00017','05:30','23:00',4,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Geared','Bajaj Pulsar 150',550,85);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Suzuki Access 125',450,70);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('blr','Whitefield Weekend Riders','Whitefield',12.969,77.712,'+91 90000 00018','08:00','20:00',4.4,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Royal Enfield','Classic 350',1400,0);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Royal Enfield','Meteor 350',1500,0);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('goa','Calangute Scooty Hire','Calangute',15.544,73.765,'+91 90000 00019','08:00','22:00',4.3,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Honda Activa 6G',400,0);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Suzuki Access 125',450,0);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('goa','Anjuna Thunder Rentals','Anjuna',15.585,73.745,'+91 90000 00020','08:00','21:00',4.6,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Royal Enfield','Classic 350',900,0);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Royal Enfield','Himalayan 450',1500,0);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('goa','Panjim Ride Stop','Panaji',15.497,73.826,'+91 90000 00021','07:00','21:00',4.2,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','TVS Jupiter',350,0);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Geared','Bajaj Avenger',800,0);

INSERT INTO shops (city,name,area,lat,lng,phone,open_time,close_time,rating,status,is_sample) VALUES ('goa','Candolim Bike Shack','Candolim',15.519,73.765,'+91 90000 00022','08:30','20:30',4.1,'approved',1);
SET @s = LAST_INSERT_ID();
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Scooter','Honda Dio',350,0);
INSERT INTO bikes (shop_id,type,model,price_day,price_hour) VALUES (@s,'Cycle','Gear cycle',200,0);
