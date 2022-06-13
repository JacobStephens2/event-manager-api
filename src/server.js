import express from 'express';
import bodyParser from 'body-parser';
import path from 'path';

const app = express();

app.use(express.static(path.join(__dirname, '/build')));

app.use(bodyParser.json());

app.get('/hello', (req, res) => res.send('Hello!'));
app.post('/hello', (req, res) => res.send(`Hello ${req.body.name}!`));

app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname + '/build/index.html'));
});

app.listen(443, () => console.log('Listening on port 443'));