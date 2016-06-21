/**
 * NewQueryRequest.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.ads;

public class NewQueryRequest  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String datasourceID;

    private java.lang.String name;

    private java.lang.String query;

    private java.lang.String _interface;

    private java.lang.String comment;

    private java.lang.String recordID;

    private java.lang.String recordFamily;

    public NewQueryRequest() {
    }

    public NewQueryRequest(
           java.lang.String ticket,
           java.lang.String datasourceID,
           java.lang.String name,
           java.lang.String query,
           java.lang.String _interface,
           java.lang.String comment,
           java.lang.String recordID,
           java.lang.String recordFamily) {
           this.ticket = ticket;
           this.datasourceID = datasourceID;
           this.name = name;
           this.query = query;
           this._interface = _interface;
           this.comment = comment;
           this.recordID = recordID;
           this.recordFamily = recordFamily;
    }


    /**
     * Gets the ticket value for this NewQueryRequest.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this NewQueryRequest.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the datasourceID value for this NewQueryRequest.
     * 
     * @return datasourceID
     */
    public java.lang.String getDatasourceID() {
        return datasourceID;
    }


    /**
     * Sets the datasourceID value for this NewQueryRequest.
     * 
     * @param datasourceID
     */
    public void setDatasourceID(java.lang.String datasourceID) {
        this.datasourceID = datasourceID;
    }


    /**
     * Gets the name value for this NewQueryRequest.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this NewQueryRequest.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the query value for this NewQueryRequest.
     * 
     * @return query
     */
    public java.lang.String getQuery() {
        return query;
    }


    /**
     * Sets the query value for this NewQueryRequest.
     * 
     * @param query
     */
    public void setQuery(java.lang.String query) {
        this.query = query;
    }


    /**
     * Gets the _interface value for this NewQueryRequest.
     * 
     * @return _interface
     */
    public java.lang.String get_interface() {
        return _interface;
    }


    /**
     * Sets the _interface value for this NewQueryRequest.
     * 
     * @param _interface
     */
    public void set_interface(java.lang.String _interface) {
        this._interface = _interface;
    }


    /**
     * Gets the comment value for this NewQueryRequest.
     * 
     * @return comment
     */
    public java.lang.String getComment() {
        return comment;
    }


    /**
     * Sets the comment value for this NewQueryRequest.
     * 
     * @param comment
     */
    public void setComment(java.lang.String comment) {
        this.comment = comment;
    }


    /**
     * Gets the recordID value for this NewQueryRequest.
     * 
     * @return recordID
     */
    public java.lang.String getRecordID() {
        return recordID;
    }


    /**
     * Sets the recordID value for this NewQueryRequest.
     * 
     * @param recordID
     */
    public void setRecordID(java.lang.String recordID) {
        this.recordID = recordID;
    }


    /**
     * Gets the recordFamily value for this NewQueryRequest.
     * 
     * @return recordFamily
     */
    public java.lang.String getRecordFamily() {
        return recordFamily;
    }


    /**
     * Sets the recordFamily value for this NewQueryRequest.
     * 
     * @param recordFamily
     */
    public void setRecordFamily(java.lang.String recordFamily) {
        this.recordFamily = recordFamily;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof NewQueryRequest)) return false;
        NewQueryRequest other = (NewQueryRequest) obj;
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
            ((this.datasourceID==null && other.getDatasourceID()==null) || 
             (this.datasourceID!=null &&
              this.datasourceID.equals(other.getDatasourceID()))) &&
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.query==null && other.getQuery()==null) || 
             (this.query!=null &&
              this.query.equals(other.getQuery()))) &&
            ((this._interface==null && other.get_interface()==null) || 
             (this._interface!=null &&
              this._interface.equals(other.get_interface()))) &&
            ((this.comment==null && other.getComment()==null) || 
             (this.comment!=null &&
              this.comment.equals(other.getComment()))) &&
            ((this.recordID==null && other.getRecordID()==null) || 
             (this.recordID!=null &&
              this.recordID.equals(other.getRecordID()))) &&
            ((this.recordFamily==null && other.getRecordFamily()==null) || 
             (this.recordFamily!=null &&
              this.recordFamily.equals(other.getRecordFamily())));
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
        if (getDatasourceID() != null) {
            _hashCode += getDatasourceID().hashCode();
        }
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getQuery() != null) {
            _hashCode += getQuery().hashCode();
        }
        if (get_interface() != null) {
            _hashCode += get_interface().hashCode();
        }
        if (getComment() != null) {
            _hashCode += getComment().hashCode();
        }
        if (getRecordID() != null) {
            _hashCode += getRecordID().hashCode();
        }
        if (getRecordFamily() != null) {
            _hashCode += getRecordFamily().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(NewQueryRequest.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusAdmin", ">NewQueryRequest"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
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
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("query");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Query"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("_interface");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Interface"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("comment");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Comment"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("recordID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RecordID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("recordFamily");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RecordFamily"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
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
