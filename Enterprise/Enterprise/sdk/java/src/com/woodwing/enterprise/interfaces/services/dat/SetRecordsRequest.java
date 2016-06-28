/**
 * SetRecordsRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public class SetRecordsRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String objectID;

    private java.lang.String datasourceID;

    private java.lang.String queryID;

    private com.woodwing.enterprise.interfaces.services.dat.QueryParam[] params;

    private com.woodwing.enterprise.interfaces.services.dat.Record[] records;

    public SetRecordsRequest() {
    }

    public SetRecordsRequest(
           java.lang.String ticket,
           java.lang.String objectID,
           java.lang.String datasourceID,
           java.lang.String queryID,
           com.woodwing.enterprise.interfaces.services.dat.QueryParam[] params,
           com.woodwing.enterprise.interfaces.services.dat.Record[] records) {
           this.ticket = ticket;
           this.objectID = objectID;
           this.datasourceID = datasourceID;
           this.queryID = queryID;
           this.params = params;
           this.records = records;
    }


    /**
     * Gets the ticket value for this SetRecordsRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this SetRecordsRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the objectID value for this SetRecordsRequest.
     * 
     * @return objectID
     */
    public java.lang.String getObjectID() {
        return objectID;
    }


    /**
     * Sets the objectID value for this SetRecordsRequest.
     * 
     * @param objectID
     */
    public void setObjectID(java.lang.String objectID) {
        this.objectID = objectID;
    }


    /**
     * Gets the datasourceID value for this SetRecordsRequest.
     * 
     * @return datasourceID
     */
    public java.lang.String getDatasourceID() {
        return datasourceID;
    }


    /**
     * Sets the datasourceID value for this SetRecordsRequest.
     * 
     * @param datasourceID
     */
    public void setDatasourceID(java.lang.String datasourceID) {
        this.datasourceID = datasourceID;
    }


    /**
     * Gets the queryID value for this SetRecordsRequest.
     * 
     * @return queryID
     */
    public java.lang.String getQueryID() {
        return queryID;
    }


    /**
     * Sets the queryID value for this SetRecordsRequest.
     * 
     * @param queryID
     */
    public void setQueryID(java.lang.String queryID) {
        this.queryID = queryID;
    }


    /**
     * Gets the params value for this SetRecordsRequest.
     * 
     * @return params
     */
    public com.woodwing.enterprise.interfaces.services.dat.QueryParam[] getParams() {
        return params;
    }


    /**
     * Sets the params value for this SetRecordsRequest.
     * 
     * @param params
     */
    public void setParams(com.woodwing.enterprise.interfaces.services.dat.QueryParam[] params) {
        this.params = params;
    }


    /**
     * Gets the records value for this SetRecordsRequest.
     * 
     * @return records
     */
    public com.woodwing.enterprise.interfaces.services.dat.Record[] getRecords() {
        return records;
    }


    /**
     * Sets the records value for this SetRecordsRequest.
     * 
     * @param records
     */
    public void setRecords(com.woodwing.enterprise.interfaces.services.dat.Record[] records) {
        this.records = records;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof SetRecordsRequest)) return false;
        SetRecordsRequest other = (SetRecordsRequest) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.objectID==null && other.getObjectID()==null) || 
             (this.objectID!=null &&
              this.objectID.equals(other.getObjectID()))) &&
            ((this.datasourceID==null && other.getDatasourceID()==null) || 
             (this.datasourceID!=null &&
              this.datasourceID.equals(other.getDatasourceID()))) &&
            ((this.queryID==null && other.getQueryID()==null) || 
             (this.queryID!=null &&
              this.queryID.equals(other.getQueryID()))) &&
            ((this.params==null && other.getParams()==null) || 
             (this.params!=null &&
              java.util.Arrays.equals(this.params, other.getParams()))) &&
            ((this.records==null && other.getRecords()==null) || 
             (this.records!=null &&
              java.util.Arrays.equals(this.records, other.getRecords())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getObjectID() != null) {
            _hashCode += getObjectID().hashCode();
        }
        if (getDatasourceID() != null) {
            _hashCode += getDatasourceID().hashCode();
        }
        if (getQueryID() != null) {
            _hashCode += getQueryID().hashCode();
        }
        if (getParams() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getParams());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getParams(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getRecords() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRecords());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRecords(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(SetRecordsRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", ">SetRecordsRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("datasourceID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DatasourceID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("queryID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "QueryID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("params");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Params"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "QueryParam"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "QueryParam"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("records");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Records"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "Record"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Record"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
