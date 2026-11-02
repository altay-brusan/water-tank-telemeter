/**
  ******************************************************************************
  * @file           : main.c
  * @brief          : Main program body
  * @author			: Köksal KURT
  ******************************************************************************
  */


/* Includes ------------------------------------------------------------------*/
#include "main.h"
#include "usart.h"
#include "gpio.h"

/* Private includes ----------------------------------------------------------*/
#include "Gosun.h"
#include "Gsm.h"
#include "stdbool.h"
#include "string.h"
#include "stdint.h"

int port1=0,port2=0,port3=0;

char OnMsg[12];
char OffMsg[12];

volatile float level=0.0f;

uint8_t modbuffer[25];
char Ulsdata[5];
char json[30];
char statusjson[80];
volatile bool Ulsdatarecieved=false;
uint8_t i = 0;
int modindex = 0;
uint8_t tempbyte;
uint32_t messageinterval = 0;
uint32_t statusinterval = 0;
uint32_t readinterval = 0;
uint32_t pinginterval = 0;
uint32_t pingerror_cnt = 0;
void Motor_Status(char * msg);
#define INTERVAL_SENSOR 600000
#define INTERVAL_STATUS 300000
#define INTERVAL_READ 2000
#define INTERVAL_PING 20000
/* Private function prototypes -----------------------------------------------*/
void SystemClock_Config(void);
/**
  * @brief  The application entry point.
  * @retval int
  */
int main(void)
{
  /* MCU Configuration--------------------------------------------------------*/

  /* Reset of all peripherals, Initializes the Flash interface and the Systick. */
  HAL_Init();

  /* Configure the system clock */
  SystemClock_Config();

  /* Initialize all configured peripherals */
  MX_GPIO_Init();
  MX_USART1_UART_Init();
  MX_USART2_UART_Init();
  MX_USART3_UART_Init();
  //Gosun_Start();
  HAL_Delay(100);

  HAL_UART_Receive_IT(&huart3, &tempbyte, 1);
  HAL_Delay(100);
  if(sim808_check_with_cmd("AT\r\n", "OK", CMD, 1000, 500)){
	  state |= STATE_ON;
  }else{
	  Gosun_switchOn();
  }

  delay(100);
  sim808_send_cmd("AT\r\n");
  sim808_flush_serial();
  delay(100);

  if(Gosun_checkSIMStatus()){
	  IF_SDEBUG("Gosun_checkSIMStatus OK");
  }
  while (!Gosun_checkCellStatus())
  {
    i++;
    if (i == 5)
    {
      state &= ~STATE_CELLED;
      IF_SDEBUG("Max Cell Search Reached");
      i = 0;
      break;
    }
    delay(2500);
  }
  if (Gosun_isCelled() && (!Gosun_isRegistered() || !Gosun_isTcpInitialized() || !Gosun_isMQTTConnected()))
    Gosun_normalConnect();
  IF_SDEBUG("Booting done. Have a good ride");
  while (1)
  {
	if ((millis() - messageinterval) > INTERVAL_SENSOR)
	{
		if(Ulsdatarecieved==true){
			for(int i=0;i<4;i++){
				Ulsdata[i]=modbuffer[i+7];
			}
			sprintf(json,"{'type':'uls','dist':%s}",Ulsdata);
			IF_SDEBUG(json);
		}
		else{
			sprintf(json,"{'type':'mec','perc':%.2f}",level);
			IF_SDEBUG(json);
		}
		if (!Gosun_MQTTpub("Level", json)){
			IF_SDEBUG("Publish Error");
			//Gosun_normalConnect();
		}else{
			IF_SDEBUG("Successfully Published");
		}

		if (!Gosun_isCelled())
		{
			i = 0;
			while (!Gosun_checkCellStatus())
			{
			  i++;
			  if (i == 5)
			  {
				state &= ~STATE_CELLED;
				IF_SDEBUG("Max Cell Search Reached");
				i = 0;
				break;
			  }
			  delay(2500);
			}
          }

        messageinterval=millis();
	}
	if ((millis() - statusinterval) > INTERVAL_STATUS)
	{
		Gosun_getSignalQualityReport();
		sprintf(statusjson,"{'uptime':%lu,'csq':%d,'port1':%1d,'port2':%1d,'port3':%1d}",millis(),rssi,port1,port2,port3);
		if (!Gosun_MQTTpub("Status", statusjson)){
			IF_SDEBUG("Status Publish Error");
			//Gosun_normalConnect();


		}else{
			IF_SDEBUG("Status Successfully Published");
		}

		statusinterval=millis();
	}
	if ((millis() - readinterval) > INTERVAL_READ)
	{
		int msgstatus = Gosun_MQTTcheckMessages();
		if (msgstatus == MQTT_MGS_FOUND)
		{
			IF_SDEBUG("There is MQTT Message");
			char* message = Gosun_MQTTgetMessage();
			Motor_Status(message);
			IF_SDEBUG(message);
		}else if(msgstatus == MQTT_ERROR) {
			Gosun_normalConnect();
		}
		readinterval = millis();
	}
	if((millis() - pinginterval) > INTERVAL_PING){
		if (!Gosun_MQTTpub("ping", "p")){
			IF_SDEBUG("Ping Error");
			pingerror_cnt++;
			if(pingerror_cnt>3){
				HAL_GPIO_WritePin(MOTOR0_GPIO_Port, MOTOR0_Pin, GPIO_PIN_RESET);
				HAL_GPIO_WritePin(MOTOR1_GPIO_Port, MOTOR1_Pin, GPIO_PIN_RESET);
				HAL_GPIO_WritePin(MOTOR2_GPIO_Port, MOTOR2_Pin, GPIO_PIN_RESET);
				Gosun_normalConnect();
			}
		}else{
			pingerror_cnt=0;
			IF_SDEBUG("Pinged");
		}
		pinginterval = millis();
	}
  }
}

/**
  * @brief System Clock Configuration
  * @retval None
  */
void SystemClock_Config(void)
{
  RCC_OscInitTypeDef RCC_OscInitStruct = {0};
  RCC_ClkInitTypeDef RCC_ClkInitStruct = {0};

  /** Initializes the CPU, AHB and APB busses clocks 
  */
  RCC_OscInitStruct.OscillatorType = RCC_OSCILLATORTYPE_HSE;
  RCC_OscInitStruct.HSEState = RCC_HSE_ON;
  RCC_OscInitStruct.HSEPredivValue = RCC_HSE_PREDIV_DIV1;
  RCC_OscInitStruct.HSIState = RCC_HSI_ON;
  RCC_OscInitStruct.PLL.PLLState = RCC_PLL_ON;
  RCC_OscInitStruct.PLL.PLLSource = RCC_PLLSOURCE_HSE;
  RCC_OscInitStruct.PLL.PLLMUL = RCC_PLL_MUL9;
  if (HAL_RCC_OscConfig(&RCC_OscInitStruct) != HAL_OK)
  {
    Error_Handler();
  }
  /** Initializes the CPU, AHB and APB busses clocks 
  */
  RCC_ClkInitStruct.ClockType = RCC_CLOCKTYPE_HCLK|RCC_CLOCKTYPE_SYSCLK
                              |RCC_CLOCKTYPE_PCLK1|RCC_CLOCKTYPE_PCLK2;
  RCC_ClkInitStruct.SYSCLKSource = RCC_SYSCLKSOURCE_PLLCLK;
  RCC_ClkInitStruct.AHBCLKDivider = RCC_SYSCLK_DIV1;
  RCC_ClkInitStruct.APB1CLKDivider = RCC_HCLK_DIV2;
  RCC_ClkInitStruct.APB2CLKDivider = RCC_HCLK_DIV1;

  if (HAL_RCC_ClockConfig(&RCC_ClkInitStruct, FLASH_LATENCY_2) != HAL_OK)
  {
    Error_Handler();
  }
}

void HAL_GPIO_EXTI_Callback(uint16_t GPIO_Pin){
	if(GPIO_Pin==INT0_Pin){
		level = 16.66f;
	}
	if(GPIO_Pin==INT1_Pin){
		level = 33.33;
	}
	if(GPIO_Pin==INT2_Pin){
		level = 50.0;
	}
	if(GPIO_Pin==INT3_Pin){
		level = 66.66f;
	}
	if(GPIO_Pin==INT4_Pin){
		level = 83.33f;
	}
}
void HAL_UART_RxCpltCallback(UART_HandleTypeDef *huart){
	if(huart->Instance==USART3){
		modbuffer[modindex]=tempbyte;
		modindex++;
		if(tempbyte=='\n'){
			modindex=0;
			Ulsdatarecieved=true;
		}
		HAL_UART_Receive_IT(&huart3, &tempbyte, 1);
	}
}
void Motor_Status(char * msg){
	char *s, *p;

	s = strchr((msg), '{');
	p = strchr((msg), ',');
	if(s != NULL){
		int i = 0;
		while(s<p){
			OnMsg[i++] = *(s++);
		}
		OnMsg[i] = '\0';
		s = p;
	}
	s = strchr((msg), '}');
	if( s != NULL){
		int i = 0;
		while(p<s){
			OffMsg[i++] = *(p++);
		}
		OffMsg[i] = '\0';
		p = s;
	}
	if(strstr(OnMsg,"ON")!=NULL){
		if(strchr(OnMsg,'0')!=NULL){
			port1=1;
			port2=1;
			port3=1;
			HAL_GPIO_WritePin(MOTOR0_GPIO_Port, MOTOR0_Pin, GPIO_PIN_SET);
			HAL_GPIO_WritePin(MOTOR1_GPIO_Port, MOTOR1_Pin, GPIO_PIN_SET);
			HAL_GPIO_WritePin(MOTOR2_GPIO_Port, MOTOR2_Pin, GPIO_PIN_SET);
		}
		if(strchr(OnMsg,'1')!=NULL){
			port1=1;
			HAL_GPIO_WritePin(MOTOR0_GPIO_Port, MOTOR0_Pin, GPIO_PIN_SET);
		}
		if(strchr(OnMsg,'2')!=NULL){
			port2=1;
			HAL_GPIO_WritePin(MOTOR1_GPIO_Port, MOTOR1_Pin, GPIO_PIN_SET);
		}
		if(strchr(OnMsg,'3')!=NULL){
			port3=1;
			HAL_GPIO_WritePin(MOTOR2_GPIO_Port, MOTOR2_Pin, GPIO_PIN_SET);
		}
	}
	if(strstr(OffMsg,"OFF")!=NULL){
		if(strchr(OffMsg,'0')!=NULL){
			port1=0;
			port2=0;
			port3=0;
			HAL_GPIO_WritePin(MOTOR0_GPIO_Port, MOTOR0_Pin, GPIO_PIN_RESET);
			HAL_GPIO_WritePin(MOTOR1_GPIO_Port, MOTOR1_Pin, GPIO_PIN_RESET);
			HAL_GPIO_WritePin(MOTOR2_GPIO_Port, MOTOR2_Pin, GPIO_PIN_RESET);
		}
		if(strchr(OffMsg,'1')!=NULL){
			port1=0;
			HAL_GPIO_WritePin(MOTOR0_GPIO_Port, MOTOR0_Pin, GPIO_PIN_RESET);
		}
		if(strchr(OffMsg,'2')!=NULL){
			port2=0;
			HAL_GPIO_WritePin(MOTOR1_GPIO_Port, MOTOR1_Pin, GPIO_PIN_RESET);
		}
		if(strchr(OffMsg,'3')!=NULL){
			port3=0;
			HAL_GPIO_WritePin(MOTOR2_GPIO_Port, MOTOR2_Pin, GPIO_PIN_RESET);
		}
	}
}
void Error_Handler(void){

}

