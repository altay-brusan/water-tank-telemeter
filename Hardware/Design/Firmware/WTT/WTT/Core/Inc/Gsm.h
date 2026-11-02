/*
 * Gsm.h
 *
 *  Created on: Mar 13, 2020
 *      Author: Pievision Technology
 */

#ifndef INC_GSM_H_
#define INC_GSM_H_

#include "main.h"
#include "stdbool.h"
#include "stdint.h"
#include <stdio.h>
#include <stdlib.h>




typedef enum  {
    CMD     = 0,
    DATA    = 1,
}DataType;
void IF_SDEBUG(const char* x);
void  sim808_init(UART_HandleTypeDef *myhuart);
//void  sim808_init(void * uart_device, uint32_t baud);
int   sim808_check_readable(void);
int   sim808_wait_readable(int wait_time);
void  sim808_flush_serial();
void  sim808_read_buffer(char* buffer,int count, unsigned int timeout, unsigned int chartimeout);
void  sim808_clean_buffer(char* buffer, int count);
void  sim808_send_byte(uint8_t data);
// void  sim808_send_char(const char c);
void  sim808_send_cmd(const char* cmd);
// void  sim808_send_cmd_P(const char* cmd);
// boolean  sim808_send_AT(void);
void  sim808_send_End_Mark(void);
bool  sim808_wait_for_resp(const char* resp, DataType type, unsigned int timeout, unsigned int chartimeout);
bool  sim808_check_with_cmd(const char* cmd, const char *resp, DataType type, unsigned int timeout, unsigned int chartimeout);
bool found(const char *buf, const char *check);
void clean_buffer(char *buffer, int count);
uint32_t millis(void);
char *str_replace(const char *orig, const char *rep, const char *with);
uint8_t countOccurrences(char *str, char *toSearch);
void delay(uint32_t delay);

#endif /* INC_GSM_H_ */
