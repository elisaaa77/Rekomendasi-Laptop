const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');
const { MongoClient, ObjectId } = require('mongodb');
const app = express();
const port = 5000;

app.use(cors());
app.use(bodyParser.json());

const uri = 'mongodb://localhost:27017';
const client = new MongoClient(uri);
const dbName = 'ecommerce';

app.post('/login', async (req, res) => {
  const { email, password } = req.body;
  await client.connect();
  const user = await client.db(dbName).collection('users').findOne({ email, password });
  if (user) {
    res.json({ user_id: user._id });
  } else {
    res.status(401).json({ error: 'Login gagal' });
  }
});

app.post('/register', async (req, res) => {
  const { name, email, password } = req.body;
  await client.connect();
  const exist = await client.db(dbName).collection('users').findOne({ email });
  if (exist) {
    res.json({ success: false, message: 'Email sudah terdaftar' });
  } else {
    const result = await client.db(dbName).collection('users').insertOne({ name, email, password });
    res.json({ success: true, user_id: result.insertedId });
  }
});

app.get('/recommendations/:user_id', async (req, res) => {
  await client.connect();
  const result = await client.db(dbName).collection('recommendations').findOne(
    { user_id: req.params.user_id },
    { projection: { _id: 0 } }
  );
  result ? res.json(result) : res.status(404).json({ error: 'Tidak ada rekomendasi' });
});

app.listen(port, () => {
  console.log(`Server API running at http://localhost:${port}`);
});
