#include <dht.h>
#include <SFE_BMP180.h>
#include <Wire.h>
#include <SPI.h>
#include <Ethernet2.h>

// ----------------------------------------- Parameters ----------------------------------------- //

int       DHT22_PIN                 = 5;

byte      MAC_ADDRESS[]             = { 0x90, 0xA2, 0xDA, 0x10, 0x75, 0xD4 };
IPAddress IP_ADDRESS                  ( 192, 168, 8, 248 ),
          SUBNET_MASK                 ( 255, 255, 255, 0 ),
          GATEWAY                     ( 192, 168, 8, 250 ),
          DNS_SERVER                  ( 192, 168, 1, 253 );

char      SERVER_HOST[]             = "meteo.iisleviponti.it";
int       SERVER_PORT               = 80;

char      SERVER_INSERT_KEY[]       = "{server_insert_key}";
char      SERVER_NOTIFY_KEY[]       = "{server_notify_key}";

long      PHASE_DURATION            = 60000;

int       ERROR_NOTIFICATION_OFFSET = 5;
int       ERROR_NOTIFICATION_PERIOD = 60;

// -------------------------------------- Global variables -------------------------------------- //

unsigned long startTime, endTime;
long delayTime;

int status;

dht DHT;
SFE_BMP180 BMP180;

EthernetClient client;

int dht22ErrorCount = 0, bmp180ErrorCount = 0;

// ---------------------------------------------------------------------------------------------- //

void setup() {

  if (!BMP180.begin()) {
    for (;;);
  }

  Ethernet.begin(MAC_ADDRESS, IP_ADDRESS, DNS_SERVER, GATEWAY, SUBNET_MASK);

  notify(0);
}

void loop() {

  startTime = millis();
  acquireAndInsert();
  endTime = millis();

  delayTime = PHASE_DURATION - (endTime - startTime);
  if (delayTime > 0) {
    delay(delayTime);
  }
}

void acquireAndInsert() {

  status = DHT.read22(DHT22_PIN);
  if (status != 0) {
    if (++dht22ErrorCount % ERROR_NOTIFICATION_PERIOD == ERROR_NOTIFICATION_OFFSET) {
      notifyWithData(1, status);
    }
    return;
  } else {
    if (dht22ErrorCount >= ERROR_NOTIFICATION_OFFSET) {
      notify(2);
    }
    dht22ErrorCount = 0;
  }

  double P;
  status = BMP180.startPressure(3);
  if (status == 0) {
    if (++bmp180ErrorCount % ERROR_NOTIFICATION_PERIOD == ERROR_NOTIFICATION_OFFSET) {
      notifyWithData(3, BMP180.getError());
    }
    return;
  } else {
    if (bmp180ErrorCount >= ERROR_NOTIFICATION_OFFSET) {
      notify(4);
    }
    bmp180ErrorCount = 0;
  }
  delay(status);
  status = BMP180.getPressure(P, DHT.temperature);
  if (status == 0) {
    if (++bmp180ErrorCount % ERROR_NOTIFICATION_PERIOD == ERROR_NOTIFICATION_OFFSET) {
      notifyWithData(3, BMP180.getError());
    }
    return;
  } else {
    if (bmp180ErrorCount >= ERROR_NOTIFICATION_OFFSET) {
      notify(4);
    }
    bmp180ErrorCount = 0;
  }

  insert(DHT.temperature, DHT.humidity, P);
}

void insert(double temperature, double humidity, double pressure) {

  char temperatureString[8];
  dtostrf(temperature, 4, 2, temperatureString);
  char humidityString[8];
  dtostrf(humidity, 4, 2, humidityString);
  char pressureString[8];
  dtostrf(pressure, 6, 2, pressureString);

  char query[128];
  sprintf(query, "key=%s&temperature=%s&humidity=%s&pressure=%s", SERVER_INSERT_KEY, temperatureString, humidityString, pressureString);

  deliver("/arduino/insert.php", query);
}

void notify(int statusId) {

  char query[128];
  sprintf(query, "key=%s&statusId=%04i", SERVER_NOTIFY_KEY, statusId);

  deliver("/arduino/notify.php", query);
}

void notifyWithData(int statusId, int statusData) {

  char query[128];
  sprintf(query, "key=%s&statusId=%04i&statusData=%04i", SERVER_NOTIFY_KEY, statusId, statusData);

  deliver("/arduino/notify.php", query);
}

void deliver(char path[], char query[]) {

  int i;
  for (i = 0; i < 4; i++) {
    client.connect(SERVER_HOST, SERVER_PORT);
    if (client.connected()) {
      break;
    }
    delay(1000);
  }
  if (!client.connected()) {
    return;
  }

  client.print  ("POST ");
  client.print  (path);
  client.println(" HTTP/1.1");
  client.print  ("Host: ");
  client.println(SERVER_HOST);
  client.println("Content-Type: application/x-www-form-urlencoded");
  client.print  ("Content-Length: ");
  client.println(strlen(query));
  client.println("Connection: close");
  client.println();
  client.println(query);

  while (client.connected()) {
    if (client.available()) {
      client.read();
    }
  }

  client.stop();
}
