/*
 * Gosun.c
 *
 *  Created on: Jul 23, 2020
 *      Author: Pievision Technology
 */


#include "Gosun.h"
#include "Gsm.h"
#include "stdio.h"
#include "stdint.h"
#include "usart.h"
char _imeicode[16];
int _gsmmode;
char msg[50];
int ber;
void Gosun_Init(void){
	sim808_init(&huart1);
    while (!sim808_check_with_cmd("AT\r\n", "OK", CMD, 500, 50))
    {
        delay(200);
    }
}

bool Gosun_isOn(void)
{
    return (state & STATE_ON);
}
bool Gosun_isInitialized(void)
{
    return (state & STATE_INITIALIZED);
}
bool Gosun_isCelled(void)
{
    return (state & STATE_CELLED);
}
bool Gosun_isRegistered(void)
{
    return (state & STATE_REGISTERED);
}
bool Gosun_isTcpInitialized(void)
{
    return (state & STATE_TCPINITIALIZED);
}
bool Gosun_isMQTTConnected(void)
{
    return (state & STATE_MQTTINITIALIZED);
}

bool Gosun_isGSMSleeping(void)
{
    return (state & STATE_SLEEPING);
}

void Gosun_send_End_Mark(void)
{
	sim808_send_byte((char)26);
}
void Gosun_switchModem(void)
{
    // IF_SDEBUG(F("YodaGsm::switchModem"));
	HAL_GPIO_WritePin(GOSUN_PWR_GPIO_Port, GOSUN_PWR_Pin, GPIO_PIN_SET);
	HAL_Delay(2500);
	HAL_GPIO_WritePin(GOSUN_PWR_GPIO_Port, GOSUN_PWR_Pin, GPIO_PIN_RESET);
}

void Gosun_switchOn(void)
{
    if (!Gosun_isOn())
    {
        IF_SDEBUG("Modem Not Powered Up");
        Gosun_switchModem();

        delay(5000);
    }
}
void Gosun_switchOff(void)
{
    // IF_SDEBUG(F("YodaGsm::switchOff"));
    if (Gosun_isOn())
    {
        if (Gosun_isTcpInitialized())
        {
        	Gosun_MQTTdisconnect();
        	Gosun_MQTTstop();
        	Gosun_TCPstop();
        }
        Gosun_switchModem();
        state &= ~STATE_ON;
    }
}
bool Gosun_TCPstart(const char *apn, const char *user, const char *pwd)
{
    // If not attached to GPRS attach to GPRS
    if (sim808_check_with_cmd("AT+CGATT?\r\n", "+CGATT: 0", CMD, 500, 50))
    {
        IF_SDEBUG("CGATT? 0 OK");
        if (!sim808_check_with_cmd("AT+CGATT=1\r\n", "OK", CMD, DEFAULT_TIMEOUT * 2, DEFAULT_INTERCHAR_TIMEOUT * 3))
        {
            IF_SDEBUG("CGATT=1 NOK");
            return false;
        }
        else
        {
            IF_SDEBUG("CGATT=1 OK");

        }
    }
    else
    {
        IF_SDEBUG("CGATT? NOK");
    }
    if (!sim808_check_with_cmd("AT+CIPQSEND=1\r\n", "OK", CMD, 500, 50))
    {
        IF_SDEBUG("CIPQSEND=1 NOK");
        return false; // fixed the second parameter minimum is 2
    }
    else
    {
        IF_SDEBUG("CIPQSEND=1 OK");
    }
    char buf[50];
    sprintf(buf,"AT+CSTT=\"%s\",\"%s\",\"%s\"\r\n",apn,user,pwd);
    //char* buf = "AT+CSTT=\"" + apn + "\",\"" + user + "\",\"" + pwd + "\"\r\n";
    // IF_SDEBUG(buf);
    if (!sim808_check_with_cmd(buf, "OK", CMD, 500, 50))
    {
        IF_SDEBUG("CSTT NOK");

        return false;
    }
    else
    {
        IF_SDEBUG("CSTT OK");

    }

    if (!sim808_check_with_cmd("AT+CIICR\r\n", "OK", CMD, DEFAULT_TIMEOUT * 2, DEFAULT_INTERCHAR_TIMEOUT * 3))
    {
        IF_SDEBUG("CIICR NOK");

        return false;
    }
    else
    {
        IF_SDEBUG("CIICR OK");
    }

    if (Gosun_TCPGetMyIP())
    {
        state |= STATE_REGISTERED;
        return true;
    }
    return false;
}
bool Gosun_TCPstop(void)
{
    state &= ~STATE_REGISTERED;
    if (!sim808_check_with_cmd("AT+CIPSHUT\r\n", "SHUT OK", CMD,DEFAULT_TIMEOUT, DEFAULT_INTERCHAR_TIMEOUT))
    {
        IF_SDEBUG("CIPSHUT NOK");
        //return false;
    }
    else
        IF_SDEBUG("CIPSHUT OK");
    // If attached to GPRS detach GPRS
    if (sim808_check_with_cmd("AT+CGATT?\r\n", "+CGATT: 1", CMD, 500, 50))
    {
        IF_SDEBUG("CGATT? 1 OK");
        if (!sim808_check_with_cmd("AT+CGATT=0\r\n", "OK", CMD, DEFAULT_TIMEOUT * 2, DEFAULT_INTERCHAR_TIMEOUT * 3))
        {
            IF_SDEBUG("CGATT=0 NOK");
            return false;
        }
        else
            IF_SDEBUG("CGATT=0 OK");
    }
    else
    {
        IF_SDEBUG("CGATT? NOK");
    }
    return true;
}
bool Gosun_TCPGetMyIP(void)
{
    char buf[20];
    char ip[16]; //255.255.255.255
    //if ((retstatus=ATcommand("+CIFSR", buf))){
    sim808_clean_buffer(buf, sizeof(buf));
    sim808_flush_serial();
    sim808_send_cmd("AT+CIFSR\r\n");
    sim808_read_buffer(buf, sizeof(buf) - 1, 500, 50);
    //IF_SDEBUG(buf);
    int token_count = sscanf(buf, "%s\r\n", ip);
    if ((token_count == 1) & !(strcmp(ip, "ERROR") == 0))
    {
        IF_SDEBUG("CIFSR OK");
        IF_SDEBUG(ip);
        return true;
    }
    else
    {
        IF_SDEBUG("CIFSR NOK");
        return false;
    }
}
bool Gosun_checkCellStatus(void){
	char buf[17];
	bool retstatus;
	sim808_clean_buffer(buf, sizeof(buf));
	sim808_flush_serial();
	sim808_send_cmd("AT+CREG?\r\n");
	sim808_read_buffer(buf, sizeof(buf) - 1, 500, 50);
	// IF_SDEBUG(buf);
	if (found(buf, "+CREG: 0,0"))
	{
		IF_SDEBUG("Cell NOT registered...");
		retstatus = false;
	}
	else if (found(buf, "+CREG: 0,1"))
	{
		IF_SDEBUG("Cell network registered...");
		state |= STATE_CELLED;
		retstatus = true;
	}
	else if (found(buf, "+CREG: 0,2"))
	{
		IF_SDEBUG("Cell searching network...");
		retstatus = false;
	}
	else if (found(buf, "+CREG: 0,3"))
	{
		IF_SDEBUG("Cell network registration denied...");
		retstatus = false;
	}
	else if (found(buf, "+CREG: 0,4"))
	{
		IF_SDEBUG("Cell unknown network...");
		retstatus = false;
	}
	else if (found(buf, "+CREG: 0,5"))
	{
		IF_SDEBUG("Cell network registered (roaming)...");
		retstatus = true;
	}
	else
	{
		IF_SDEBUG("CREG NOK");
		retstatus = false;
	}
	if(retstatus==true)
		state |= STATE_CELLED;
	else
		state &= ~STATE_CELLED;

	return retstatus;
}
bool Gosun_checkSIMStatus(void){
    int count = 0;
    while (count < 5)
    {
        if (sim808_check_with_cmd("AT+CPIN?\r\n", "+CPIN: READY", CMD, 500, 50))
        {
            IF_SDEBUG("CPIN? OK");
            break;
        }
        else
        {
        	printf("CPIN? NOK");
            count++;
            delay(1000);
        }
    }
    if (count == 5)
    {
        IF_SDEBUG("CPIN? Reach Max retry");
        return false;
    }

    if (!Gosun_getIMEI())
        return false;

    return true;
}
bool Gosun_setGSMMode(uint8_t mode)
{
    char tmp[15];
    _gsmmode = GSM_MODE_FULL;
    sprintf(tmp, "AT+CFUN=%i\r\n", _gsmmode);
    if (sim808_check_with_cmd(tmp, "OK", CMD, 500, 50))
    {
        IF_SDEBUG("Gosun_setGSMMode OK");
        return true;
    }
    else
    {
        IF_SDEBUG("Gosun_setGSMMode NOK");
        return false;
    }
}
bool Gosun_getIMEI(void)
{
    char EGMR_buf[26]; //BUF_LENGTH
    bool retstatus = false;

    sim808_clean_buffer(EGMR_buf, sizeof(EGMR_buf));
    sim808_flush_serial();
    // send_cmd(F("AT+EGMR=0,7\r\n"));
    sim808_send_cmd("AT+CGSN\r\n");
    sim808_read_buffer(EGMR_buf, sizeof(EGMR_buf), 500, 50);
    // IF_SDEBUG(EGMR_buf);
    uint8_t token_count = sscanf(EGMR_buf, "\r\n%s\r\nOK", _imeicode);
    if (token_count == 1)
    {
        IF_SDEBUG("CGSN OK");
        IF_SDEBUG(_imeicode);
        if (strlen(_imeicode) < 15)
        {
            strcpy(_imeicode, "000000000000000");
            retstatus = false;
        }
        else
            retstatus = true;
    }
    else
    {
        IF_SDEBUG("CGSN NOK");
        strcpy(_imeicode, "000000000000000");
        IF_SDEBUG(_imeicode);
        retstatus = false;
    }
    return retstatus;
}
void Gosun_getSignalQualityReport(void)
{
    /*
    returns received signal strength indication <rssi>
    and channel bit error rate <ber>
  */

    char CSQ_buf[20];
    //  if(!isRegistered()) return false;
    sim808_clean_buffer(CSQ_buf, sizeof(CSQ_buf));
    sim808_flush_serial();
    sim808_send_cmd("AT+CSQ\r\n");
    sim808_read_buffer(CSQ_buf, sizeof(CSQ_buf) - 1, DEFAULT_TIMEOUT, DEFAULT_INTERCHAR_TIMEOUT);
    IF_SDEBUG(CSQ_buf);
    if (found(CSQ_buf, "+CSQ: "))
    {
    	sscanf(CSQ_buf, "\r\n+CSQ:%d,%d\r\n", &rssi, &ber);
    }
}
uint8_t Gosun_getGSMMode(void)
{
    // char buf[15];
    char CFUN_buf[20];
    uint8_t retstatus;
    // clean_buffer(buf, sizeof(buf));
    sim808_flush_serial();
    sim808_send_cmd("AT+CFUN?\r\n");
    sim808_read_buffer(CFUN_buf, 15, 500, 50);
    if (found(CFUN_buf, "+CFUN: 0"))
    {
        IF_SDEBUG("Minimum functionality");
        retstatus = 0;
    }
    else if (found(CFUN_buf, "+CFUN: 1"))
    {
        IF_SDEBUG("Full functionality");
        retstatus = 1;
    }
    else if (found(CFUN_buf, "+CFUN: 4"))
    {
        IF_SDEBUG("Flight functionality");
        retstatus = 4;
    }
    else
    {
        IF_SDEBUG("CFUN NOK");
        retstatus = 99;
    }
    return retstatus;
}

void Gosun_MQTTsetServer(const char* server, uint16_t port)
{
	strcpy(_mqtt_server,server);

    _mqtt_port = port;
    IF_SDEBUG("Gosun_MQTTsetServer OK");
}


bool Gosun_MQTTinit(const char* clientid, const char* username, const char* password)
{

    char mconfig[100];

    // IF_SDEBUG(mconfig);
    if (!sim808_check_with_cmd("AT+MQTTMSGSET=1\r\n", "OK", CMD, 500, 50))
    {
        IF_SDEBUG("MQTTMSGSET NOK");
    }
    else
    {
        IF_SDEBUG("MQTTMSGSET OK");
    }
    sprintf(mconfig, "AT+MCONFIG=\"%s\",\"%s\",\"%s\",0,0\r\n", clientid, username, password);
    if (!sim808_check_with_cmd(mconfig, "OK", CMD, 5000, 1000))
    {
        IF_SDEBUG("MCONFIG NOK");
        return false;
    }
    else
    {
        IF_SDEBUG("MCONFIG OK");
        return true;
    }
}

/**
 * @brief bool Gosun_MQTTconnect(void)
 *
 * @return true
 * @return false
 */
bool Gosun_MQTTconnect(void)
{
    char MCONNECT_buf[22];
    clean_buffer(MCONNECT_buf, sizeof(MCONNECT_buf));
    sim808_flush_serial();
    // AT+MCONNECT
    if (!sim808_check_with_cmd("AT+MCONNECT=0,30\r\n", "OK", CMD, DEFAULT_TIMEOUT*3, DEFAULT_INTERCHAR_TIMEOUT * 5))
    {
        IF_SDEBUG("Gosun_MQTTconnect NOK");
        return false;
    }
    else
        IF_SDEBUG("Gosun_MQTTconnect OK");

    state |= STATE_TCPINITIALIZED;
    return true;
}

/**
 * @brief bool Gosun_MQTTdisconnect(void)
 *
 * @return true
 * @return false
 */
bool Gosun_MQTTdisconnect(void)
{
	 state &= ~STATE_MQTTINITIALIZED;
    //AT+MDISCONNECT
    if (!sim808_check_with_cmd("AT+MDISCONNECT\r\n", "CLOSED", CMD, DEFAULT_TIMEOUT*2, DEFAULT_INTERCHAR_TIMEOUT * 2))
    {
        IF_SDEBUG("Gosun_MQTTdisconnect NOK");
        return false;
    }
    else
        IF_SDEBUG("Gosun_MQTTdisconnect OK");

    state &= ~STATE_TCPINITIALIZED;
    return true;
}

/**
 * @brief bool Gosun_MQTTstop()
 *
 * @return true
 * @return false
 */
bool Gosun_MQTTstop()
{
    // AT+MIPCLOSE
	state &= ~STATE_TCPINITIALIZED;
    if (!sim808_check_with_cmd("AT+MIPCLOSE\r\n", "CLOSED", CMD, DEFAULT_TIMEOUT, DEFAULT_INTERCHAR_TIMEOUT))
    {
        IF_SDEBUG("Gosun_MQTTstop NOK");
        return false;
    }
    else
        IF_SDEBUG("Gosun_MQTTstop OK");

    return true;
}

/**
 * @brief bool Gosun_MQTTstart(char *server, uint16_t port)
 *
 * @param server
 * @param port
 * @return true
 * @return false
 */
bool Gosun_MQTTstart(const char* server, uint16_t port)
{

    // char buf[BUF_LENGTH];
	strcpy(_mqtt_server,server);
    _mqtt_port = port;
    char MIPSTART_buf[50];
    bool retstatus = false;
    char mipstart[50];
    sprintf(mipstart,"AT+MIPSTART=\"%s\",%u\r\n",server,port);
    //char* mipstart = "AT+MIPSTART=\"" + _mqtt_server + "\"," + char*(_mqtt_port) + "\r\n";
    // IF_SDEBUG(mipstart);
    sim808_flush_serial();
    sim808_send_cmd(mipstart);
    sim808_read_buffer(MIPSTART_buf, 21, DEFAULT_TIMEOUT * 4, DEFAULT_INTERCHAR_TIMEOUT * 8);
    if (found(MIPSTART_buf, "CONNECT OK"))
    {
        IF_SDEBUG("Gosun_MQTTstart OK");
        state |= STATE_TCPINITIALIZED;
        retstatus = true;
    }
    else if (found(MIPSTART_buf, "ALREADY CONNECT"))
    {
        IF_SDEBUG("Gosun_MQTTstart AOK");
        state |= STATE_TCPINITIALIZED;
        retstatus = true;
    }
    else
    {
        IF_SDEBUG("Gosun_MQTTstart NOK");
        retstatus = false;
    }
    return retstatus;
}

/**
 * @brief bool Gosun_MQTTsub(char * topic)
 *
 * @param topic
 * @return true
 * @return false
 */
bool Gosun_MQTTsub(char *topic)
{
    char msub[40 + strlen(topic)];
    char responsebuffer[100];
    sprintf(msub, "AT+MSUB=\"water-tanks/%s/%s\",1\r\n", _imeicode, topic);
    IF_SDEBUG(msub);
    sim808_flush_serial();
    sim808_send_cmd(msub);
    sim808_read_buffer(responsebuffer, 99, DEFAULT_TIMEOUT, DEFAULT_INTERCHAR_TIMEOUT*2);
    IF_SDEBUG(responsebuffer);
//    if (!sen(msub, "SUBACK", CMD, DEFAULT_TIMEOUT, DEFAULT_INTERCHAR_TIMEOUT * 2))
//    {
//        IF_SDEBUG("Gosun_MQTTsub NOK");
//        return false;
//    }
//    else{
//        IF_SDEBUG("Gosun_MQTTsub OK");
//    }


    return true;
}

/**
 * @brief bool Gosun_MQTTpub(char * topic, char * payload)
 *
 * @param topic
 * @param payload
 * @return true
 * @return false
 */
bool Gosun_MQTTpub(char *topic, char *payload)
{
    // delay(50);

    char mpub[50 + strlen(topic) + strlen(payload)];
    // Serial.print(F("Topic =")); Serial.println(topic);
    // Serial.print(F("Payload =")); Serial.println(payload);
    // Serial.println(sizeof(mpub));
    // strcat(mpub, "AT+MPUB=\"gps-logger/");
    // strcat(mpub, Gosun__imeicode);
    // strcat(mpub, "/");
    // strcat(mpub, topic);
    // strcat(mpub, "\",0,0,\"");
    // strcat(mpub, payload);
    // strcat(mpub,"\"");
    // sprintf(mpub, "AT+MPUB=\"gps-logger/%s/%s\",1,0,\"%s\"\r\n", Gosun__imeicode, topic, payload);
    // // IF_SDEBUG(mpub);
    // if (!check_with_cmd(mpub, "PUBACK", CMD, 2000, 500))
    sprintf(mpub, "AT+MPUB=\"water-tanks/%s/%s\",0,0,\"%s\"\r\n", _imeicode,topic, payload);
    IF_SDEBUG(mpub);
    delay(500);
    if (!sim808_check_with_cmd(mpub, "OK", CMD, DEFAULT_TIMEOUT, DEFAULT_INTERCHAR_TIMEOUT * 2))
    {
        IF_SDEBUG("Gosun_MQTTpub NOK");
        return false;
    }
    else
        IF_SDEBUG("Gosun_MQTTpub OK");
    return true;
}

/**
 * @fn bool Gosun_checkMQTTMessages(void)
 * @brief Check mqtt message slots
 * @return uint8_t Count
 * @note have to test
 */
int Gosun_MQTTcheckMessages(void)
{

    // 18:28:14.247 -> AT+MQTTMSGGET?
    // 18:28:14.284 -> +MQTTMSGGET :0,VALID
    // 18:28:14.284 -> +MQTTMSGGET :1,VALID
    // 18:28:14.321 -> +MQTTMSGGET :2,INVALID
    // 18:28:14.321 -> +MQTTMSGGET :3,INVALID
    // 18:28:14.321 ->
    // 18:28:14.321 -> OK
    char buf[26];
    sim808_clean_buffer(buf, 25);
    sim808_flush_serial();
    sim808_send_cmd("AT+MQTTMSGGET?\r\n");
    sim808_read_buffer(buf, 25, 500, 50);
    if(found(buf,"ERROR")){
    	return MQTT_ERROR;
    }
    IF_SDEBUG(buf);
    if (found(buf, "+MQTTMSGGET :0,VALID")){
    	return MQTT_MGS_FOUND;
    }
    else{
    	return MQTT_NO_MSG;
    }
}

/**
 * @fn char *Gosun_getMQTTMessage(void)
 * @brief Read mqtt messages and assign to mqtt_messages;
*/
char* Gosun_MQTTgetMessage(void) //uint8_t count)
{
    // 18:28:17.512 -> AT+MQTTMSGGET
    // 18:28:17.549 -> +MSUB: gps-logger/867823030091174/Action,8 byte,{'ping'}
    // 18:28:17.587 -> +MSUB: gps-logger/867823030091174/Action,8 byte,{'ping'}
    // 18:28:17.587 -> +MSUB: gps-logger/867823030091174/Action,8 byte,{'ping'}
    // 18:28:17.587 -> +MSUB: gps-logger/867823030091174/Action,8 byte,{'ping'}
    // 18:28:17.587 -> OK
    //   char mqtt_messages[50];
    char msgget_Buffer[150];

    char *s, *p;
    uint8_t i = 0;
    //uint8_t count = checkMQTTMessages();

    sim808_clean_buffer(msgget_Buffer, sizeof(msgget_Buffer));
    sim808_flush_serial();
    sim808_send_cmd("AT+MQTTMSGGET\r\n");
    sim808_read_buffer(msgget_Buffer, sizeof(msgget_Buffer), 1000, 100);
    // IF_SDEBUG(msgget_Buffer);
    if (NULL != (s = strstr(msgget_Buffer, "+MSUB:")))
    {
        // for (uint8_t x = 0; x < count; x++)
        // {
        s = strstr((char *)(s), "{");
        p = strstr((char *)(s), "\r\n"); //p is last character """
        if (NULL != s)
        {
            i = 0;
            while (s < p)
                msg[i++] = *(s++);

            msg[i] = '\0';
            // s = p;
        }
        //   strcpy(mqtt_messages[x], msg);
        // strcpy(mqtt_messages, msg);
        // Serial.println(msg);
        // Gosun_make_debug(mqtt_messages[x]);
        //}
        IF_SDEBUG("Gosun_MQTTgetMessage OK");
        return msg;
    }
    IF_SDEBUG("Gosun_MQTTgetMessage NOK");
    return msg;
}
bool Gosun_checkMQTTStatus(void){
	bool status=true;
	if(im808_check_with_cmd("AT+MCONNECT=?", "ERROR", CMD, 500, 100)){
		status=false;
	}
}
void Gosun_normalConnect(void){
	IF_SDEBUG("Connection Steps");
    uint8_t i = 0;
	Gosun_MQTTdisconnect();
	Gosun_MQTTstop();
	Gosun_TCPstop();
    delay(100);
    Gosun_TCPstart("internet", "", "");
    if (Gosun_isRegistered())
    {
    	Gosun_MQTTsetServer(DEFAULT_MQTT_SERVER, DEFAULT_MQTT_PORT);
    	Gosun_MQTTinit(_imeicode, _imeicode, _imeicode);

        i = 0;
        while (!Gosun_MQTTstart(DEFAULT_MQTT_SERVER, DEFAULT_MQTT_PORT))
        {
            i++;
            if (i == 5)
                break;
            delay(1000);
        }
    }
    if (Gosun_isTcpInitialized())
    {
        i = 0;
        while (!Gosun_MQTTconnect())
        {
            i++;
            if (i == 5)
                break;
            delay(1000);
        }
    }
    delay(5000);
	Gosun_MQTTsub("Action");
}

