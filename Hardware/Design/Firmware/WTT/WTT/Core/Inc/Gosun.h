/*
 * Gosun.h
 *
 *  Created on: Jul 23, 2020
 *      Author: Pievision Technology
 */

#ifndef INC_GOSUN_H_
#define INC_GOSUN_H_

#include "main.h"
#include "stdint.h"
#include "string.h"
#include "stdbool.h"

void Gosun_Start(void);

char _mqtt_server[20];
uint16_t _mqtt_port;
uint8_t state;
int rssi;

#define MQTT_ERROR 0
#define MQTT_MGS_FOUND 1
#define MQTT_NO_MSG 2
#define DEFAULT_TIMEOUT 2500
#define DEFAULT_INTERCHAR_TIMEOUT 1000
#define DEFAULT_GPS_INTERVAL 1000
#define DEFAULT_GPS_PWR_PULSE_TIMEOUT 2000

#define BUF_LENGTH 100
#define BUFCOMMAND_LENGTH 120

#define STATE_NONE 0
#define STATE_ON 2
#define STATE_INITIALIZED 4
#define STATE_CELLED 8           //CELL CONNECTED
#define STATE_REGISTERED 16      //INTERNET CONNECTED
#define STATE_TCPINITIALIZED 32  //TCP CONNECTED
#define STATE_MQTTINITIALIZED 64 //MQTT CONNECTED
#define STATE_SLEEPING 128

#define GSM_MODE_MIN 0
#define GSM_MODE_FULL 1
#define GSM_MODE_FLIGHT 4

#define DEFAULT_MQTT_SERVER "mqtt.example.com" /* set to your MQTT broker host */
#define DEFAULT_MQTT_PORT 1883

bool Gosun_getIMEI(void);
void Gosun_getSignalQualityReport(void);
bool Gosun_checkCellStatus(void);
bool Gosun_checkSIMStatus(void);
bool Gosun_setGSMMode(uint8_t mode);
uint8_t Gosun_getGSMMode(void);

void Gosun_switchModem(void);
void Gosun_switchOn(void);
void Gosun_switchOff(void);
bool Gosun_transparentescape(void);
bool Gosun_transparent(void);

bool Gosun_TCPstart(const char *apn, const char *user, const char *pwd);
// bool TCPconnect(String server, int port);
bool Gosun_TCPGetMyIP(void);
// bool TCPdisconnect(void);
bool Gosun_TCPstop(void);

bool Gosun_isOn(void);
bool Gosun_isInitialized(void);
bool Gosun_isCelled(void);
bool Gosun_isRegistered(void);
bool Gosun_isTcpInitialized(void);
bool Gosun_isMQTTConnected(void);
bool Gosun_isGSMSleeping(void);


bool Gosun_MQTTinit(const char* clientid, const char* username, const char* password);
bool Gosun_MQTTconnect(void);
bool Gosun_MQTTdisconnect(void);
bool Gosun_MQTTstop(void);
bool Gosun_MQTTstart(const char* server, uint16_t port);
bool Gosun_MQTTsub(char* topic);
bool Gosun_MQTTpub(char* topic, char* payload);
int Gosun_MQTTcheckMessages(void);
char* Gosun_MQTTgetMessage(void); //uint8_t count);
void Gosun_MQTTsetServer(const char* server, uint16_t port);
bool Gosun_MQTTupload(char* logfilename, uint8_t Type);
void Gosun_normalConnect(void);

#endif /* INC_GOSUN_H_ */
