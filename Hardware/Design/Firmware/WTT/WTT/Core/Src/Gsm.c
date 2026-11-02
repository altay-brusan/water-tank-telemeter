/*
 * Gsm.cpp
 *
 *  Created on: Mar 13, 2020
 *      Author: Pievision Technology
 */
#include "Gsm.h"
#include "string.h"
#include "usart.h"

UART_HandleTypeDef SimSerial;
int timeout=2500;
int chartimeout=1000;

void IF_SDEBUG(const char* x){
	char msg[strlen(x)+10];
	sprintf(msg,"%lu\t %s \r\n",millis(),x);
	HAL_UART_Transmit(&huart2, (uint8_t*)msg, strlen(msg), 200);
}

void  sim808_init(UART_HandleTypeDef *myhuart){
	SimSerial=*myhuart;
}

int sim808_check_readable(void){

	uint32_t RX_State;
    RX_State = huart1.Instance->SR & UART_FLAG_RXNE;
    //something is in the buffer
    if (RX_State > 0)
    {
        return 1;
    }
    //nothing is there
    return 0;
}
void sim808_flush_serial()
{
    while(sim808_check_readable()){
    	uint8_t tmp;
        HAL_UART_Receive(&huart1, &tmp, 1, 1);
    }
}

void sim808_read_buffer(char *buffer, int count, unsigned int timeout, unsigned int chartimeout)
{
    int i = 0;
    unsigned long timerStart, prevChar;
    timerStart = millis();
    prevChar = 0;
    while(1) {
        while (sim808_check_readable()) {
        	uint8_t c;
            HAL_UART_Receive(&huart1, &c, 1, 1);
            prevChar = millis();
            buffer[i++] = c;
            if(i >= count)break;
        }
        if(i >= count)break;
        if ((unsigned long) (millis() - timerStart) > timeout/*  * 100UL */) {
            break;
        }
        //If interchar Timeout => return FALSE. So we can return sooner from this function. Not DO it if we dont recieve at least one char (prevChar <> 0)
        if (((unsigned long) (millis() - prevChar) > chartimeout) && (prevChar != 0)) {
            break;
        }
    }
}
void sim808_clean_buffer(char *buffer, int count)
{
    for(int i=0; i < count; i++) {
        buffer[i] = '\0';
    }
}
//HACERR quitar esta funcion ?
void sim808_send_byte(uint8_t data)
{
	HAL_UART_Transmit(&huart1, &data, 1, 1);
}

void sim808_send_cmd(const char* cmd)
{
  for(uint16_t i=0; i<strlen(cmd); i++)
    {
        sim808_send_byte(cmd[i]);
    }
}

bool sim808_wait_for_resp(const char* resp, DataType type, unsigned int timeout, unsigned int chartimeout)
{
    int len = strlen(resp);
    int sum = 0;
    unsigned long timerStart, prevChar;    //prevChar is the time when the previous Char has been read.
    timerStart = millis();
    prevChar = 0;
    while(1) {
        if(sim808_check_readable()) {
        	uint8_t c;
            HAL_UART_Receive(&huart1, &c, 1, 0);
            prevChar = millis();
            sum = (c==resp[sum]) ? sum+1 : 0;
            if(sum == len)break;
        }
        if ((unsigned long) (millis() - timerStart) > timeout /* * 1000UL */) {
            // Serial.println(F("Time Out"));
            return false;
        }
        //If interchar Timeout => return FALSE. So we can return sooner from this function.
        if (((unsigned long) (millis() - prevChar) > chartimeout) && (prevChar != 0)) {
            // Serial.println(F("Char Time Out"));
            return false;
        }

    }
    //If is a CMD, we will finish to read buffer.
    if(type == CMD) sim808_flush_serial();
    return true;
}

bool sim808_check_with_cmd(const char* cmd, const char *resp, DataType type, unsigned int timeout, unsigned int chartimeout)
{

    sim808_send_cmd(cmd);
    return sim808_wait_for_resp(resp,type,timeout,chartimeout);
}
bool found(const char *buf, const char *check)
{
    //IF_SDEBUG(F("in:"));
    //IF_SDEBUG(buf);
    //IF_SDEBUG(F("search:"));
    if (!check)
    {
        //IF_SDEBUG(F("NONE"));
        return false;
    }
    //IF_SDEBUG(check);
    //return !strstr(buf-strlen(check), check);
    return strstr(buf, check);
}
void clean_buffer(char *buffer, int count)
{
    for (int i = 0; i < count; i++)
    {
        buffer[i] = '\0';
    }
}

uint32_t millis(void){
	return HAL_GetTick();
}
char *str_replace(const char *orig, const char *rep, const char *with)
{
    char *result;    // the return string
    const char *ins; // the next insert point
    char *tmp;       // varies
    int len_rep;     // length of rep
    int len_with;    // length of with
    int len_front;   // distance between rep and end of last rep
    int count;       // number of replacements

    if (!orig)
        return NULL;
    if (!rep)
        rep = "";
    len_rep = strlen(rep);
    if (!with)
        with = "";
    len_with = strlen(with);

    ins = orig;
    for (count = 0; (tmp = strstr(ins, rep)); ++count)
    {
        ins = tmp + len_rep;
    }

    // first time through the loop, all the variable are set correctly
    // from here on,
    //    tmp points to the end of the result string
    //    ins points to the next occurrence of rep in orig
    //    orig points to the remainder of orig after "end of rep"
    tmp = result = (char *)malloc(strlen(orig) + (len_with - len_rep) * count + 1);

    if (!result)
        return NULL;

    while (count--)
    {
        ins = strstr(orig, rep);
        len_front = ins - orig;
        tmp = strncpy(tmp, orig, len_front) + len_front;
        tmp = strcpy(tmp, with) + len_with;
        orig += len_front + len_rep; // move to next "end of rep"
    }
    strcpy(tmp, orig);
    return result;
}
uint8_t countOccurrences(char *str, char *toSearch)
{
    int i, j, found, count;
    int stringLen, searchLen;

    stringLen = strlen(str);      // length of string
    searchLen = strlen(toSearch); // length of word to be searched

    count = 0;

    for (i = 0; i <= stringLen - searchLen; i++)
    {
        /* Match word with string */
        found = 1;
        for (j = 0; j < searchLen; j++)
        {
            if (str[i + j] != toSearch[j])
            {
                found = 0;
                break;
            }
        }

        if (found == 1)
        {
            count++;
        }
    }

    return count;
}
void delay(uint32_t delay){
	HAL_Delay(delay);
}
