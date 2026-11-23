/* =========================================================
   GoLocal Wheels – DATA FILE
   ---------------------------------------------------------
   Edit this file to add or change shops. No other file needs
   to change.

   HOW TO ADD A REAL SHOP
   1. Open the shop in Google Maps, right-click on its pin and
      click the numbers at the top (e.g. 11.93452, 79.82851).
      That copies the latitude, longitude.
   2. Copy one shop block below, paste it, and change the values.
   3. Give every shop a different id number.
   4. Bike types must be one of:
      'Scooter', 'Geared', 'Royal Enfield', 'Electric', 'Cycle'
   5. hr = price per hour (use 0 if the shop only rents per day)

   NOTE: All shops below are SAMPLE DATA (invented names,
   prices and phone numbers). Replace them with real shops.
   ========================================================= */

const SAMPLE_DATA = true;   // change to false once all shops are real

const CITIES = {
  pondy: {
    name: 'Puducherry', state: 'Puducherry',
    center: [11.94, 79.825], zoom: 13,
    routes: [
      {name: 'ECR coastal ride to Serenity Beach & Auroville', km: 14, color: '#d2461f',
       pts: [[11.9337,79.8337],[11.9455,79.8365],[11.9640,79.8405],[11.9895,79.8455],[12.0000,79.8300],[12.0067,79.8105]],
       tip: 'Go early morning (6–8 AM). Sea breeze, little traffic. Stop at Serenity Beach on the way.'},
      {name: 'Heritage White Town loop', km: 5, color: '#2f8fa6',
       pts: [[11.9337,79.8337],[11.9320,79.8355],[11.9285,79.8350],[11.9270,79.8305],[11.9350,79.8290],[11.9337,79.8337]],
       tip: 'Short, slow ride through French Quarter streets. Park and walk the Promenade in the evening, when Beach Road closes to vehicles.'},
      {name: 'South to Paradise Beach (Chunnambar)', km: 9, color: '#7a5cc7',
       pts: [[11.9270,79.8305],[11.9150,79.8280],[11.9000,79.8220],[11.8890,79.8180]],
       tip: 'Ride to the Chunnambar boat house, then take the boat across to the beach.'}
    ],
    fuel: [
      ['petrol','Petrol bunk (sample)',11.940,79.822],['petrol','Petrol bunk (sample)',11.915,79.824],
      ['petrol','Petrol bunk (sample)',11.980,79.836],['ev','EV charging (sample)',11.936,79.826],
      ['ev','EV charging (sample)',12.003,79.820]
    ],
    tips: [
      ['Documents','Carry original driving licence + one ID (Aadhaar). Most shops keep a copy or a deposit.'],
      ['Helmets','Helmet is compulsory for rider and pillion. Ask the shop for a second helmet.'],
      ['Parking','Beach Road (Goubert Ave) is closed to vehicles in the evening. Park on side streets in White Town.']
    ]
  },
  chennai: {
    name: 'Chennai', state: 'Tamil Nadu', center: [13.04, 80.25], zoom: 12,
    routes: [{name: 'ECR ride towards Mahabalipuram (start)', km: 18, color: '#d2461f',
      pts: [[13.0000,80.2660],[12.9800,80.2560],[12.9500,80.2520],[12.9150,80.2470]],
      tip: 'Full ride to Mahabalipuram is ~55 km. Good weekend trip.'}],
    fuel: [['petrol','Petrol bunk (sample)',13.06,80.26],['petrol','Petrol bunk (sample)',13.02,80.23],['ev','EV charging (sample)',13.04,80.24]],
    tips: [['Traffic','Avoid OMR / Anna Salai at peak hours (8–11 AM, 5–8 PM).'],['Deposit','Expect a refundable deposit for bigger bikes.'],['Helmets','Helmet rules are strictly enforced in Chennai.']]
  },
  blr: {
    name: 'Bengaluru', state: 'Karnataka', center: [12.97, 77.62], zoom: 12,
    routes: [{name: 'City to Nandi Hills (start of route)', km: 60, color: '#d2461f',
      pts: [[12.9750,77.6070],[13.0000,77.6000],[13.0350,77.5970],[13.0800,77.6000]],
      tip: 'Leave by 4:30 AM to catch sunrise at Nandi Hills.'}],
    fuel: [['petrol','Petrol bunk (sample)',12.96,77.60],['ev','EV charging (sample)',12.935,77.62],['petrol','Petrol bunk (sample)',12.99,77.64]],
    tips: [['Weekends','Bikes for Nandi Hills / Coorg trips get booked out. Reserve by Thursday.'],['Metro + bike','Many shops are near metro stations. Pick up after your metro ride.'],['Rain','Monsoon months: ask for a raincoat along with the bike.']]
  },
  goa: {
    name: 'North Goa', state: 'Goa', center: [15.54, 73.79], zoom: 12,
    routes: [{name: 'Beach hop: Candolim → Calangute → Baga → Anjuna', km: 12, color: '#d2461f',
      pts: [[15.5190,73.7650],[15.5440,73.7650],[15.5560,73.7560],[15.5850,73.7450]],
      tip: 'Best in the evening. Avoid riding on the beach sand.'}],
    fuel: [['petrol','Petrol bunk (sample)',15.55,73.77],['petrol','Petrol bunk (sample)',15.50,73.83],['ev','EV charging (sample)',15.53,73.78]],
    tips: [['Yellow plate','Rent only bikes with yellow/black commercial number plates. That means they are licensed for rental.'],['Deposit','Keep your shop’s receipt. Photograph the bike before leaving.'],['Night riding','Roads are dark in many areas. Ride carefully after sunset.']]
  }
};

/* bike helper: B(type, model, pricePerDay, pricePerHour) */
const B = (type, model, day, hr) => ({type, model, day, hr});

const SHOPS = [
  /* ---------- Puducherry (SAMPLE) ---------- */
  {id:1, city:'pondy', name:'Mission Street Wheels', area:'Mission Street', lat:11.9345, lng:79.8285,
   phone:'+91 90000 00001', whatsapp:'', rating:4.6, open:'07:00', close:'21:00',
   bikes:[B('Scooter','Honda Activa 6G',350,60), B('Scooter','TVS Jupiter',350,60), B('Geared','Bajaj Pulsar 150',500,80)]},
  {id:2, city:'pondy', name:'French Quarter Rides', area:'White Town', lat:11.9325, lng:79.8335,
   phone:'+91 90000 00002', whatsapp:'', rating:4.8, open:'08:00', close:'20:00',
   bikes:[B('Royal Enfield','Classic 350',1100,0), B('Royal Enfield','Himalayan 450',1600,0), B('Scooter','Suzuki Access 125',400,70)]},
  {id:3, city:'pondy', name:'Bus Stand Bike Point', area:'New Bus Stand', lat:11.9385, lng:79.8170,
   phone:'+91 90000 00003', whatsapp:'', rating:4.2, open:'06:00', close:'22:00',
   bikes:[B('Scooter','Honda Dio',300,50), B('Scooter','Honda Activa 5G',300,50), B('Geared','Hero Splendor',300,50), B('Geared','Honda Shine',350,60)]},
  {id:4, city:'pondy', name:'Station Road Rentals', area:'Railway Station', lat:11.9275, lng:79.8285,
   phone:'+91 90000 00004', whatsapp:'', rating:4.4, open:'05:30', close:'22:30',
   bikes:[B('Scooter','TVS Ntorq',400,70), B('Electric','Ather 450X',500,90), B('Geared','Yamaha FZ',550,90)]},
  {id:5, city:'pondy', name:'Auroville Cycle & Scoot', area:'Auroville', lat:12.0060, lng:79.8120,
   phone:'+91 90000 00005', whatsapp:'', rating:4.7, open:'07:00', close:'19:00',
   bikes:[B('Cycle','Hercules Roadeo',120,20), B('Cycle','Gear cycle',180,30), B('Electric','Ola S1',450,80), B('Scooter','Honda Activa 6G',380,60)]},
  {id:6, city:'pondy', name:'Serenity Shore Rentals', area:'Kottakuppam / Serenity Beach', lat:11.9630, lng:79.8370,
   phone:'+91 90000 00006', whatsapp:'', rating:4.3, open:'08:00', close:'20:00',
   bikes:[B('Scooter','Suzuki Access 125',380,60), B('Royal Enfield','Bullet 350',1000,0)]},
  {id:7, city:'pondy', name:'Green Volt EV Rentals', area:'MG Road', lat:11.9365, lng:79.8255,
   phone:'+91 90000 00007', whatsapp:'', rating:4.5, open:'09:00', close:'21:00',
   bikes:[B('Electric','Ather Rizta',480,85), B('Electric','TVS iQube',450,80), B('Electric','Bajaj Chetak',450,80)]},
  {id:8, city:'pondy', name:'Lawspet Bike Hub', area:'Lawspet', lat:11.9580, lng:79.8180,
   phone:'+91 90000 00008', whatsapp:'', rating:4.1, open:'08:00', close:'20:30',
   bikes:[B('Scooter','Honda Dio',280,50), B('Geared','Bajaj Platina',280,45)]},
  {id:9, city:'pondy', name:'Muthialpet Motors', area:'Muthialpet', lat:11.9460, lng:79.8300,
   phone:'+91 90000 00009', whatsapp:'', rating:4.0, open:'07:30', close:'21:00',
   bikes:[B('Geared','KTM Duke 200',1200,0), B('Royal Enfield','Hunter 350',950,0), B('Scooter','TVS Jupiter',330,55)]},
  {id:10, city:'pondy', name:'Ariyankuppam Beach Rides', area:'Ariyankuppam', lat:11.9050, lng:79.8170,
   phone:'+91 90000 00010', whatsapp:'', rating:4.2, open:'08:00', close:'19:00',
   bikes:[B('Scooter','Honda Activa 6G',330,55), B('Cycle','City cycle',100,20)]},

  /* ---------- Chennai (SAMPLE) ---------- */
  {id:11, city:'chennai', name:'Marina Ride Co.', area:'Triplicane', lat:13.0550, lng:80.2740, phone:'+91 90000 00011', whatsapp:'', rating:4.4, open:'07:00', close:'22:00',
   bikes:[B('Scooter','Honda Activa 6G',450,70), B('Royal Enfield','Classic 350',1300,0)]},
  {id:12, city:'chennai', name:'T Nagar Two-Wheelers', area:'T. Nagar', lat:13.0400, lng:80.2360, phone:'+91 90000 00012', whatsapp:'', rating:4.1, open:'08:00', close:'21:00',
   bikes:[B('Scooter','TVS Ntorq',480,80), B('Geared','Yamaha R15',1100,0)]},
  {id:13, city:'chennai', name:'Besant Nagar EV Hub', area:'Besant Nagar', lat:13.0020, lng:80.2640, phone:'+91 90000 00013', whatsapp:'', rating:4.6, open:'09:00', close:'21:00',
   bikes:[B('Electric','Ather 450X',550,90), B('Cycle','Gear cycle',200,35)]},
  {id:14, city:'chennai', name:'Central Station Bikes', area:'Park Town', lat:13.0820, lng:80.2720, phone:'+91 90000 00014', whatsapp:'', rating:4.0, open:'05:00', close:'23:00',
   bikes:[B('Scooter','Honda Dio',400,65), B('Geared','Honda Shine',420,65)]},

  /* ---------- Bengaluru (SAMPLE) ---------- */
  {id:15, city:'blr', name:'Koramangala Moto Rentals', area:'Koramangala', lat:12.9340, lng:77.6260, phone:'+91 90000 00015', whatsapp:'', rating:4.5, open:'07:00', close:'22:00',
   bikes:[B('Royal Enfield','Himalayan 450',1800,0), B('Geared','KTM Duke 390',1900,0), B('Scooter','TVS Ntorq',500,80)]},
  {id:16, city:'blr', name:'Indiranagar Scoot Stop', area:'Indiranagar', lat:12.9790, lng:77.6400, phone:'+91 90000 00016', whatsapp:'', rating:4.3, open:'08:00', close:'21:00',
   bikes:[B('Scooter','Honda Activa 6G',450,70), B('Electric','Ola S1 Pro',600,95)]},
  {id:17, city:'blr', name:'Majestic Bike Point', area:'Majestic', lat:12.9780, lng:77.5730, phone:'+91 90000 00017', whatsapp:'', rating:4.0, open:'05:30', close:'23:00',
   bikes:[B('Geared','Bajaj Pulsar 150',550,85), B('Scooter','Suzuki Access 125',450,70)]},
  {id:18, city:'blr', name:'Whitefield Weekend Riders', area:'Whitefield', lat:12.9690, lng:77.7120, phone:'+91 90000 00018', whatsapp:'', rating:4.4, open:'08:00', close:'20:00',
   bikes:[B('Royal Enfield','Classic 350',1400,0), B('Royal Enfield','Meteor 350',1500,0)]},

  /* ---------- North Goa (SAMPLE) ---------- */
  {id:19, city:'goa', name:'Calangute Scooty Hire', area:'Calangute', lat:15.5440, lng:73.7650, phone:'+91 90000 00019', whatsapp:'', rating:4.3, open:'08:00', close:'22:00',
   bikes:[B('Scooter','Honda Activa 6G',400,0), B('Scooter','Suzuki Access 125',450,0)]},
  {id:20, city:'goa', name:'Anjuna Thunder Rentals', area:'Anjuna', lat:15.5850, lng:73.7450, phone:'+91 90000 00020', whatsapp:'', rating:4.6, open:'08:00', close:'21:00',
   bikes:[B('Royal Enfield','Classic 350',900,0), B('Royal Enfield','Himalayan 450',1500,0)]},
  {id:21, city:'goa', name:'Panjim Ride Stop', area:'Panaji', lat:15.4970, lng:73.8260, phone:'+91 90000 00021', whatsapp:'', rating:4.2, open:'07:00', close:'21:00',
   bikes:[B('Scooter','TVS Jupiter',350,0), B('Geared','Bajaj Avenger',800,0)]},
  {id:22, city:'goa', name:'Candolim Bike Shack', area:'Candolim', lat:15.5190, lng:73.7650, phone:'+91 90000 00022', whatsapp:'', rating:4.1, open:'08:30', close:'20:30',
   bikes:[B('Scooter','Honda Dio',350,0), B('Cycle','Gear cycle',200,0)]}
];
