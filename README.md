# Commission Calculation System

Author: Dagemawi Alemayehu

## Introduction

This system calculates commission fees for deposit and withdrawal operations based on user type (private or business) and transaction currency.

Time Spent
Approximately 12 hours were spent on completing this project. This includes time for development, testing, and documentation.

## Features
Calculates commission fees for both private and business users.
Handles different currencies and converts them as needed.
Applies free withdrawal limits for private users within a calendar week.
Extensible and maintainable code structure.
Includes an automated test to verify the calculations.

## Project Structure
```
├── src
│   ├── Command
│   │   └── ProcessCsvCommand.php
│   ├── Model
│   │   └── Operation.php
│   ├── Service
│   │   ├── CommissionCalculator.php
│   │   └── CurrencyConverter.php
├── tests
│   └── CommissionCalculatorTest.php
├── vendor
│   └── autoload.php
├── composer.json
└── README.md
└── input.csv
└── script.php
```
## Installation
Clone the repository:

``` git clone https://github.com/DagemawiDeveloper/CommissionApp-Dagemawi.git ```

``` cd CommissionApp-Dagemawi ```

## Install dependencies:

``` composer install ```

## Requirements

- PHP 7.4 or higher
- Composer

## Setup

1. Download the code.
2. Navigate to the project directory.
3. Install dependencies:
   ```bash
   composer install
   ```
4. Running the Application
   ```
   php script.php input.csv
   ```

## Usage

To run the commission calculation script with an input file:

```bash
php script.php input.csv
```
