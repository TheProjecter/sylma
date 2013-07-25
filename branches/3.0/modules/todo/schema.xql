<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2013.sylma,org/modules/todo"
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"
>

  <table name="todo">
    <field name="id" type="sql:id"/>
    <field name="description" type="sql:string-long"/>
    <field name="url" title="Page" type="sql:string"/>
    <field name="insertion" type="sql:datetime" default="now()" alter-default="null"/>
    <field name="priority" type="sql:int" default="0"/>
    <field name="statut" type="sql:int" default="0"/>
  </table>

</schema>
