FROM node:20-alpine

WORKDIR /var/www

RUN npm install -g npm@latest

COPY package*.json ./
RUN npm install

COPY . .

