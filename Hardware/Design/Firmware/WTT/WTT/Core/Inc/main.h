/* USER CODE BEGIN Header */
/**
  ******************************************************************************
  * @file           : main.h
  * @brief          : Header for main.c file.
  *                   This file contains the common defines of the application.
  ******************************************************************************
  * @attention
  *
  * <h2><center>&copy; Copyright (c) 2020 STMicroelectronics.
  * All rights reserved.</center></h2>
  *
  * This software component is licensed by ST under BSD 3-Clause license,
  * the "License"; You may not use this file except in compliance with the
  * License. You may obtain a copy of the License at:
  *                        opensource.org/licenses/BSD-3-Clause
  *
  ******************************************************************************
  */
/* USER CODE END Header */

/* Define to prevent recursive inclusion -------------------------------------*/
#ifndef __MAIN_H
#define __MAIN_H

#ifdef __cplusplus
extern "C" {
#endif

/* Includes ------------------------------------------------------------------*/
#include "stm32f1xx_hal.h"

/* Private includes ----------------------------------------------------------*/
/* USER CODE BEGIN Includes */

/* USER CODE END Includes */

/* Exported types ------------------------------------------------------------*/
/* USER CODE BEGIN ET */

/* USER CODE END ET */

/* Exported constants --------------------------------------------------------*/
/* USER CODE BEGIN EC */

/* USER CODE END EC */

/* Exported macro ------------------------------------------------------------*/
/* USER CODE BEGIN EM */

/* USER CODE END EM */

/* Exported functions prototypes ---------------------------------------------*/
void Error_Handler(void);

/* USER CODE BEGIN EFP */

/* USER CODE END EFP */

/* Private defines -----------------------------------------------------------*/
#define INT0_Pin GPIO_PIN_0
#define INT0_GPIO_Port GPIOA
#define INT0_EXTI_IRQn EXTI0_IRQn
#define INT1_Pin GPIO_PIN_1
#define INT1_GPIO_Port GPIOA
#define INT1_EXTI_IRQn EXTI1_IRQn
#define INT2_Pin GPIO_PIN_4
#define INT2_GPIO_Port GPIOA
#define INT2_EXTI_IRQn EXTI4_IRQn
#define INT3_Pin GPIO_PIN_5
#define INT3_GPIO_Port GPIOA
#define INT3_EXTI_IRQn EXTI9_5_IRQn
#define INT4_Pin GPIO_PIN_6
#define INT4_GPIO_Port GPIOA
#define INT4_EXTI_IRQn EXTI9_5_IRQn
#define MOTOR2_Pin GPIO_PIN_0
#define MOTOR2_GPIO_Port GPIOB
#define MOTOR0_Pin GPIO_PIN_1
#define MOTOR0_GPIO_Port GPIOB
#define MOTOR1_Pin GPIO_PIN_2
#define MOTOR1_GPIO_Port GPIOB
#define INT5_Pin GPIO_PIN_12
#define INT5_GPIO_Port GPIOB
#define INT5_EXTI_IRQn EXTI15_10_IRQn
#define INT6_Pin GPIO_PIN_13
#define INT6_GPIO_Port GPIOB
#define INT6_EXTI_IRQn EXTI15_10_IRQn
#define INT7_Pin GPIO_PIN_14
#define INT7_GPIO_Port GPIOB
#define INT7_EXTI_IRQn EXTI15_10_IRQn
#define GOSUN_PWR_Pin GPIO_PIN_8
#define GOSUN_PWR_GPIO_Port GPIOA
#define LED_Pin GPIO_PIN_12
#define LED_GPIO_Port GPIOA
/* USER CODE BEGIN Private defines */

/* USER CODE END Private defines */

#ifdef __cplusplus
}
#endif

#endif /* __MAIN_H */

/************************ (C) COPYRIGHT STMicroelectronics *****END OF FILE****/
